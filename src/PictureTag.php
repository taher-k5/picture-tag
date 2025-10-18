<?php

namespace taherkathiriya\craftpicturetag;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\PluginEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\UrlManager;
use craft\web\View;
use taherkathiriya\craftpicturetag\models\Settings;
use taherkathiriya\craftpicturetag\services\ImageService;
use taherkathiriya\craftpicturetag\services\TemplateService;
use taherkathiriya\craftpicturetag\twigextensions\PictureTagTwigExtension;
use yii\base\Event;

/**
 * Picture Tag plugin
 *
 * @author Taher Kathiriya <taher@example.com>
 * @copyright Taher Kathiriya
 * @license https://craftcms.github.io/license/ Craft License
 */
class PictureTag extends BasePlugin
{
    public static PictureTag $plugin;
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'imageService' => ImageService::class,
                'templateService' => TemplateService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            $this->registerTwigExtensions();
        });

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->registerCpUrlRules();
            $this->registerRedirectAfterInstall();
        }
    }

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    // protected function settingsHtml(): ?string
    // {
    //     try {
    //         return Craft::$app->getView()->renderTemplate('picture-tag/_settings', [
    //             'plugin' => $this,
    //             'settings' => $this->getSettings(),
    //         ]);
    //     } catch (\Exception $e) {
    //         return '<p>Error loading settings: ' . $e->getMessage() . '</p>';
    //     }
    // }

    private function attachEventHandlers(): void
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['picture-tag'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            }
        );
    }
    private function registerTwigExtensions(): void
    {
        Craft::$app->getView()->registerTwigExtension(new PictureTagTwigExtension());
    }

    /**
     * Registers CP URL rules
     */
    private function registerCpUrlRules(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                // Merge so that settings controller action comes first (important!)
                $event->rules = array_merge([
                    'settings/plugins/picture-tag' => 'picture-tag/settings/edit',
                ],
                    $event->rules
                );
            }
        );
    }

    /**
     * Registers redirect after install
     */
    private function registerRedirectAfterInstall(): void
    {
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                    // Redirect to settings page with welcome
                    Craft::$app->getResponse()->redirect(
                        UrlHelper::cpUrl('settings/plugins/picture-tag', [
                            'welcome' => 1,
                        ])
                    )->send();
                }
            }
        );
    }
}