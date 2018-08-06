<?php
/**
 * Protected Links plugin for Craft CMS 3.x
 *
 * Secure & restricted files download
 *
 * @link      http://www.intoeetive.com/
 * @copyright Copyright (c) 2018 Yurii Salimovskyi
 */

namespace intoeetive\protectedlinks\variables;

use intoeetive\protectedlinks\ProtectedLinks;

use Craft;
use yii\base\Exception;
use craft\db\Query;
use craft\helpers\UrlHelper;

/**
 * Protected Links Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.protectedLinks }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Yurii Salimovskyi
 * @package   ProtectedLinks
 * @since     0.0.1
 */
class ProtectedLinksVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.protectedLinks.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.protectedLinks.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function link($passedParams = [])
    {
        $paramsAvailable = [
            'siteId' => Craft::$app->sites->getPrimarySite()->id,
            'assetId' => 0,
            'denyHotlink' => 0,
            'requireLogin' => 0,
            'memberGroups' => '',
            'members' => '',
            'inline' => 0,
            'mimeType' => '',
            'dateExpires' => null,
            'downloads' => 0
        ];
        
        $params = [];
        
        foreach ($paramsAvailable as $param=>$defaultVal)
        {
            if (isset($passedParams[$param]))
            {
                if (in_array($param, ['members', 'memberGroups']))
                {
                    if (!is_array($passedParams[$param]))
                    {
                        $passedParams[$param] = array($passedParams[$param]);
                    }
                    $params[$param] = serialize($passedParams[$param]);
                    $params['requireLogin'] = 1;
                }
                else
                {
                    $params[$param] = $passedParams[$param];
                }
            }
            else
            {
                $params[$param] = $defaultVal;
            }
        }
        
        if (empty($params['assetId']))
        {
            throw new Exception(Craft::t('protectedlinks', 'assetId cannot be empty'));
        }
        
        $params['checksum'] = sha1(serialize($params));
        
        $link = (new Query())
                ->select('id')
                ->from('{{%protectedlinks_links}}')
                ->where(['checksum' =>  $params['checksum']])
                ->one();
        if (!empty($link))
        {
            return UrlHelper::actionUrl('protectedlinks/link/get', ['code' =>  $params['checksum']]);
        }
        
        Craft::$app->getDb()->createCommand()->insert('{{%protectedlinks_links}}', $params)->execute();
        
        $insertId = Craft::$app->getDb()->getLastInsertID();

        return UrlHelper::actionUrl('protectedlinks/link/get', ['code' =>  $params['checksum']]);
    }
}
