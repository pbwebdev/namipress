<?php

/**
 * @package ThemePlate
 * @since   0.1.0
 */

namespace PBWebDev\CardanoPress;

use PBWebDev\CardanoPress\Actions\CoreAction;
use PBWebDev\CardanoPress\Actions\WalletAction;
use ThemePlate\Enqueue;

class Application
{
    private static Application $instance;
    private Admin $admin;
    private Templates $templates;
    public const VERSION = '0.18.0';

    public static function instance(): Application
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        register_activation_hook(CARDANOPRESS_FILE, [$this, 'activate']);
        $this->setup();

        add_action('plugins_loaded', [$this, 'loaded'], -1);
        add_action('init', [Enqueue::class, 'init']);
    }

    public function activate(): void
    {
        if ('yes' === get_transient('cardanopress_activating')) {
            return;
        }

        set_transient('cardanopress_activating', 'yes', MINUTE_IN_SECONDS * 2);

        if (empty(get_option('cardanopress_version'))) {
            $this->templates->createPages();
        }

        update_option('cardanopress_version', self::VERSION);
        delete_transient('cardanopress_activating');
    }

    private function setup(): void
    {
        $load_path = plugin_dir_path(CARDANOPRESS_FILE);

        $this->templates = new Templates($load_path . 'templates');

        new Manifest($load_path . 'assets');
        new CoreAction();
        new WalletAction();
        new Shortcode();

        $this->admin = new Admin();
    }

    public function loaded(): void
    {
        do_action('cardanopress_loaded');
    }

    public function option(string $key)
    {
        return $this->admin->getOption($key);
    }

    public function template(string $name, array $variables = []): void
    {
        $name .= '.php';
        $file = locate_template($this->templates->getPath() . $name);

        if (! $file) {
            $file = $this->templates->getPath(true) . $name;
        }

        if (file_exists($file)) {
            extract($variables, EXTR_OVERWRITE);
            include $file;
        }
    }

    public function userProfile(): Profile
    {
        static $user;

        if (null === $user) {
            $user = wp_get_current_user();
        }

        return new Profile($user);
    }

    public function delegationPool(): array
    {
        static $data;

        if (null !== $data) {
            return $data;
        }

        $network = $this->userProfile()->connectedNetwork();

        if (! $network) {
            return [];
        }

        $poolData = cardanoPress()->option('delegation_pool_data');
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $data = $poolData[$network] ?? [];

        return $data;
    }

    public function isReady(): bool
    {
        $projectIds = $this->option('blockfrost_project_id');
        $projectIds = array_filter($projectIds);

        return ! empty($projectIds);
    }

    public function isTemplatePage($post = null): bool
    {
        $template = get_page_template_slug($post);

        return $this->templates->isCustomPage($template);
    }
}
