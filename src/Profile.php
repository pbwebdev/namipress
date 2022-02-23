<?php

/**
 * @package ThemePlate
 * @since   0.1.0
 */

namespace PBWebDev\CardanoPress;

use WP_User;

class Profile
{
    protected WP_User $user;
    private string $prefix = 'cardanopress_';

    public function __construct(WP_User $user)
    {
        $this->user = $user;
    }

    public function isConnected(): bool
    {
        return $this->connectedNetwork() && $this->connectedWallet() && $this->connectedStake();
    }

    public function getMetaKey(string $name, $prefix = null): string
    {
        if (null === $prefix) {
            $prefix = $this->prefix;
        }

        return $prefix . $name;
    }


    public function connectedNetwork(): string
    {
        $saved = get_user_meta($this->user->ID, $this->getMetaKey('connected_network'), true);

        return $saved ?? '';
    }

    public function saveNetwork(string $query_network): bool
    {
        $isAdded = false;

        if ($query_network !== $this->connectedNetwork()) {
            $isAdded = update_user_meta($this->user->ID, $this->getMetaKey('connected_network'), $query_network);
        }

        return (bool)$isAdded;
    }

    public function connectedWallet(): string
    {
        $saved = get_user_meta($this->user->ID, $this->getMetaKey('connected_wallet'), true);

        return $saved ?? '';
    }

    public function saveWallet(string $walletAddress): bool
    {
        $isAdded = false;

        if ($walletAddress !== $this->connectedWallet()) {
            $isAdded = update_user_meta($this->user->ID, $this->getMetaKey('connected_wallet'), $walletAddress);
        }

        return (bool)$isAdded;
    }

    public function connectedStake(): string
    {
        $saved = get_user_meta($this->user->ID, $this->getMetaKey('connected_stake'), true);

        return $saved ?? '';
    }

    public function saveStake(string $stakeAddress): bool
    {
        $isAdded = false;

        if ($stakeAddress !== $this->connectedStake()) {
            $isAdded = update_user_meta($this->user->ID, $this->getMetaKey('connected_stake'), $stakeAddress);
        }

        return (bool)$isAdded;
    }

    public function storedAssets(): array
    {
        $saved = get_user_meta($this->user->ID, $this->getMetaKey('stored_assets'), true);

        return array_filter((array)$saved);
    }

    public function saveAssets(array $data): bool
    {
        return update_user_meta($this->user->ID, $this->getMetaKey('stored_assets'), $data);
    }

    public function allTransactions(): array
    {
        $saved = get_user_meta($this->user->ID, $this->getMetaKey('transaction'), false);

        return array_filter((array)$saved);
    }

    public function saveTransaction(string $network, string $action, string $hash): bool
    {
        $data = compact('network', 'action', 'hash');
        $isSaved = add_user_meta($this->user->ID, $this->getMetaKey('transaction'), $data);

        return (bool)$isSaved;
    }

    public function setUserAuth(string $username): void
    {
        wp_set_current_user($this->user->ID);
        wp_set_auth_cookie($this->user->ID);
        do_action('wp_login', $username, $this->user);
    }

    public function unsetUserAuth(): void
    {
        wp_destroy_current_session();
        wp_clear_auth_cookie();
        wp_set_current_user(0);
        do_action('wp_logout', $this->user->ID);
    }

    public function getData(string $key = null)
    {
        if (null === $key) {
            return $this->user;
        }

        return $this->user->$key;
    }

    public function addRole(string $role): void
    {
        $this->user->add_role($role);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->user->roles, true);
    }
}
