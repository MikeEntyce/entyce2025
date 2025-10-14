<?php

namespace Duplicator\Utils\OAuth;

use Duplicator\Utils\OAuth\TokenEntity;

/**
 * This class is responsible for handling communication with the OAuth2 servers.
 */
class TokenService
{
    /** @var int Storage type identifier */
    protected $storage_type = 0;

    /**
     * Create a new instance of the service.
     *
     * @param int $storage_type Storage type identifier
     */
    public function __construct($storage_type)
    {
        $this->storage_type = (int) $storage_type;
    }

    /**
     * Get a list of servers capable of handling the OAuth2 requests.
     *
     * @return string[]
     */
    private static function getServerCandidates()
    {
        return [
            DUPLICATOR_PRO_PRIMARY_OAUTH_SERVER,
            DUPLICATOR_PRO_SECONDARY_OAUTH_SERVER,
        ];
    }

    /**
     * Get the server to use for OAuth2 requests.
     *
     * @param bool $check Should we check if the server is online.
     *
     * @return string
     */
    private static function getServer($check = false)
    {
        $candidates = self::getServerCandidates();

        if (! $check) {
            return $candidates[0];
        }

        foreach ($candidates as $candidate) {
            if (self::checkServer($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * Check if the server is online.
     *
     * @param string $server Server URL.
     *
     * @return bool
     */
    private static function checkServer($server)
    {
        $url = sprintf('%s/check', $server);

        $response = wp_remote_get($url, ['timeout' => 5]);

        if (is_wp_error($response)) {
            return false;
        }

        return true;
    }

    /**
     * Get the redirect uri for the current provider.
     *
     * @return string
     */
    public function getRedirectUri()
    {
        return sprintf('%s/oauth/%s/connect', self::getServer(), $this->storage_type);
    }

    /**
     * Refresh the token from the server.
     *
     * @param TokenEntity $token The token entity to be refreshed.
     *
     * @return void
     * @throws \Exception
     */
    public function refreshToken(TokenEntity $token)
    {
        // We must check if the server is live before we try to refresh the token.
        $url = sprintf('%s/oauth/%s/refresh', self::getServer(true), $this->storage_type);

        $response = wp_remote_post($url, [
            'timeout' => 5,
            'body'    => [
                'refresh_token' => $token->getRefreshToken(),
            ],
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return;
        }

        $token->updateProperties(json_decode($body, true));
    }
}
