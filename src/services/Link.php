<?php
/**
 * Protected Links plugin for Craft CMS 3.x
 *
 * Secure & restricted files download
 *
 * @link      http://www.intoeetive.com/
 * @copyright Copyright (c) 2018 Yurii Salimovskyi
 */

namespace intoeetive\protectedlinks\services;

use intoeetive\protectedlinks\ProtectedLinks;

use Craft;
use craft\base\Component;
use craft\db\Query;

/**
 * Link Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Yurii Salimovskyi
 * @package   ProtectedLinks
 * @since     0.0.1
 */
class Link extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     ProtectedLinks::$plugin->link->exampleService()
     *
     * @return mixed
     */
    public function downloads($assetId)
    {
        if (empty($assetId))
        {
            return null;
        }
        
        $count = (new Query())
                ->select('SUM(downloads) AS sum')
                ->from('{{%protectedlinks_links}}')
                ->where(['assetId' =>  $assetId])
                ->one();

        return (int)$count['sum'];
    }
}
