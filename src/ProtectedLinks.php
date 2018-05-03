<?php
/**
 * Protected Links plugin for Craft CMS 3.x
 *
 * Secure & restricted files download
 *
 * @link      http://www.intoeetive.com/
 * @copyright Copyright (c) 2018 Yurii Salimovskyi
 */

namespace intoeetive\protectedlinks;

use intoeetive\protectedlinks\services\Link as LinkService;
use intoeetive\protectedlinks\variables\ProtectedLinksVariable;
use intoeetive\protectedlinks\actions\Downloads;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use craft\base\Element;
use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use craft\events\RegisterElementSortOptionsEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\events\CancelableEvent;

use craft\db\Query;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Yurii Salimovskyi
 * @package   ProtectedLinks
 * @since     0.0.1
 *
 * @property  LinkService $link
 */
class ProtectedLinks extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * ProtectedLinks::$plugin
     *
     * @var ProtectedLinks
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '0.0.1';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * ProtectedLinks::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'protected-links/link';
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('protectedLinks', ProtectedLinksVariable::class);
            }
        );
        
        /*Event::on(
            Asset::class, 
            Element::EVENT_REGISTER_ACTIONS, 
            function(RegisterElementActionsEvent $event) 
            {
                $event->actions[] = Downloads::class;
            }
        );*/
        
        Event::on(
            Asset::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function(RegisterElementTableAttributesEvent $e) {
                $e->tableAttributes['downloads'] = [
                    'label' => Craft::t('protected-links', 'Downloads')
                ];
        });
        
        Event::on(
            Asset::class,
            Element::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES,
            function(RegisterElementDefaultTableAttributesEvent $e) {
                $e->tableAttributes[] = 'downloads';
        });
        
        /*Event::on(
            Asset::class,
            Element::EVENT_REGISTER_SORT_OPTIONS,
            function(RegisterElementSortOptionsEvent $e) {
                //$e->sortOptions['protectedlinks_links.downloads'] = Craft::t('protected-links', 'Downloads');
        });*/
        
        /*Event::on(
            Asset::class,
            Element::EVENT_REGISTER_SOURCES,
            function(RegisterElementSourcesEvent $e) {
                //Craft::dd($e);
        });*/
        
        Event::on(
            Asset::class,
            Element::EVENT_SET_TABLE_ATTRIBUTE_HTML,
            function(craft\events\SetElementTableAttributeHtmlEvent $e) {
                if($e->attribute === 'downloads'){
                    $e->html = $this->link->downloads($e->sender->id);
                }
            }
        );
        
        /*Event::on(
            Asset::class,
            Element::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES,
            function(craft\events\RegisterElementSearchableAttributesEvent $e) {
                $e->attributes[] = 'downloads';
            }
        );*/
        
        
        /*Event::on(
            ElementQuery::class,
            ElementQuery::EVENT_AFTER_PREPARE,
            function(CancelableEvent $e) {
                if ($e->sender->elementType == 'craft\\elements\\Asset')
                {
                    $e->sender->query->addSelect('linkstats.downloads');
                    
                    $subQuery = (new Query())
                        ->select('assetId, SUM(downloads) AS downloads')
                        ->from('{{%protectedlinks_links}}')
                        ->groupBy('assetId');
                    
                    $e->sender->query->leftJoin(
                        ['linkstats' => $subQuery], 'assets.id = linkstats.assetId'
                    );
                    //Craft::dd($e->sender->query);
                }
            }
        );*/
        

        // Do something after we're installed
        /*Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );*/

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'protected-links',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
