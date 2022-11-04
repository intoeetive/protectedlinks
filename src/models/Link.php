<?php
/**
 * Protected Links plugin for Craft CMS 4.x
 *
 * Secure & restricted files download
 *
 * @link      http://www.intoeetive.com/
 * @copyright Copyright (c) 2018 Yurii Salimovskyi
 */

namespace intoeetive\protectedlinks\models;

use intoeetive\protectedlinks\ProtectedLinks;

use Craft;
use craft\base\Model;

/**
 * Link Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Yurii Salimovskyi
 * @package   ProtectedLinks
 * @since     0.0.1
 */
class Link extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some model attribute
     *
     * @var string
     */
    public $siteId = 1;
    public $assetId = 0;
    public $checksum = '';
    public $denyHotlink = 0;
    public $requireLogin = 0;
    public $memberGroups = '';
    public $members = '';
    public $inline = 0;
    public $mimeType = 0;
    public $dateExpires = null;
    public $downloads = 0;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ['assetId', 'integer']
        ];
    }
}
