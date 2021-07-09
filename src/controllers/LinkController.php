<?php
/**
 * Protected Links plugin for Craft CMS 3.x
 *
 * Secure & restricted files download
 *
 * @link      http://www.intoeetive.com/
 * @copyright Copyright (c) 2018 Yurii Salimovskyi
 */

namespace intoeetive\protectedlinks\controllers;

use intoeetive\protectedlinks\ProtectedLinks;

use Craft;
use craft\web\Controller;
use craft\db\Query;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use craft\helpers\UrlHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\FileHelper;

/**
 * Link Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Yurii Salimovskyi
 * @package   ProtectedLinks
 * @since     0.0.1
 */
class LinkController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'get'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/protectedlinks/link
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the LinkController actionIndex() method';

        return $result;
    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/protectedlinks/link/do-something
     *
     * @return mixed
     */
    public function actionGet()
    {
        $code = Craft::$app->getRequest()->getRequiredParam('code');
        
        $member_id = Craft::$app->getUser()->getId();
        
        $link = (new Query())
                ->select('*')
                ->from('{{%protectedlinks_links}}')
                ->where(['checksum' =>  $code])
                ->one();

        if (empty($link))
        {
            throw new Exception(Craft::t('protectedlinks', 'Link not found'));
        }
        
        if (!empty($link['requireLogin']) && !$member_id)
        {
            throw new ForbiddenHttpException(Craft::t('protectedlinks', 'You need to log in to access this file'));
        }
        
        if (!empty($link['denyHotlink']))
        {
            $site_url_a = explode("/", str_replace('https://www.', '', str_replace('http://www.', '', UrlHelper::baseSiteUrl())));
            $site_url = $site_url_a[0];
            if (strpos(Craft::$app->getRequest()->getReferrer(), $site_url)===false)
            {
                throw new ForbiddenHttpException(Craft::t('protectedlinks', 'Hotlinking not allowed for this file'));
            }
        }
        
        if (!empty($link['dateExpires']))
        {
            if (DateTimeHelper::isInThePast($link['dateExpires']))
            {
                throw new ForbiddenHttpException(Craft::t('protectedlinks', 'Link has expired'));
            }
        }
        
        if (!empty($link['members']) && Craft::$app->getUser()->getIdentity()->admin===false)
        {
            if (!$member_id)
            {
                throw new ForbiddenHttpException(Craft::t('protectedlinks', 'You need to log in to access this file'));
            }
            $members = unserialize($link['members']);
            if (!in_array($member_id, $members))
            {
                throw new ForbiddenHttpException(Craft::t('protectedlinks', 'You are not allowed to access this file'));
            }
        }
        
        if (!empty($link['memberGroups']) && Craft::$app->getUser()->getIdentity()->admin===false)
        {
            if (!$member_id)
            {
                throw new ForbiddenHttpException(Craft::t('protectedlinks', 'You need to log in to access this file'));
            }
            $memberGroups = unserialize($link['memberGroups']);
            $isInGroup = false;
            foreach ($memberGroups as $group)
            {
                if (Craft::$app->getUser()->getIdentity()->isInGroup($group))
                {
                    $isInGroup = true;
                }
            }
            if (!$isInGroup)
            {
                throw new ForbiddenHttpException(Craft::t('protectedlinks', 'You are not allowed to access this file'));
            }
        }
        
        $assetService = Craft::$app->getAssets();

        $asset = $assetService->getAssetById($link['assetId']);

        if (!$asset) {
            throw new BadRequestHttpException(Craft::t('app', 'The Asset you’re trying to download does not exist.'));
        }

        //if membership is not required, asset permissions can be ignored
        if (!empty($link['requireLogin']) || !empty($link['members']) || !empty($link['memberGroups'])) {
            $this->_requirePermissionByAsset('viewVolume', $asset);
        }
        
        //update downloads counter
        Craft::$app->getDb()->createCommand()->update('{{%protectedlinks_links}}', ['downloads'=>$link['downloads']+1], ['id'=>$link['id']])->execute();

        // All systems go, engage hyperdrive! (so PHP doesn't interrupt our stream)
        App::maxPowerCaptain();
        $localPath = $asset->getCopyOfFile();
        
        $downloadOptions = [];
        if (!empty($link['mimeType']))
        {
            $downloadOptions['mimeType'] = $link['mimeType'];
        }
        if (!empty($link['inline']))
        {
            $downloadOptions['inline'] = true;
        }

        $response = Craft::$app->getResponse()
            ->sendFile($localPath, $asset->filename, $downloadOptions);
        FileHelper::unlink($localPath);

        return $response;
    }
    
    
        /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param Asset $asset Asset on the Volume on which to require the permission.
     */
    private function _requirePermissionByAsset(string $permissionName, craft\elements\Asset $asset)
    {
        if (!$asset->volumeId) {
            $userTemporaryFolder = Craft::$app->getAssets()->getCurrentUserTemporaryUploadFolder();

            // Skip permission check only if it's the user's temporary folder
            if ($userTemporaryFolder->id == $asset->folderId) {
                return;
            }
        }

        // choose $volumeId by Craft version
        if (version_compare(Craft::$app->getInfo()->version, "3.1", "<")) {
            // 3.0.x
            $volumeId = $asset->volumeId;
        } else {
            //3.1.x
            $volumeId = $asset->getVolume()->uid;
        }
        if (!Craft::$app->getUser()->checkPermission($permissionName.':'.$volumeId)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    
    
}
