<?php

declare(strict_types=1);

use Slim\App;
use GuzzleHttp\Client;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\Album\ListAlbumAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });


    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->group('/api/v1', function (Group $group) {
        $group->post('/token', function (Request $request, Response $response) {
            try {
                $client = new Client(['base_uri' => 'https://accounts.spotify.com/api/']);
                $res = $client->request('POST', 'token', [
                    'headers' => [
                        'Authorization' => 'Basic NjZjMTc5ZTJkNzg1NDkxM2E2NTI1ZTE2OWFhZjVhMWE6ZGVlODU5NWQ3MDZlNDIzYWJhNDRkNTg4OTdhM2U2OTQ='
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials'
                    ]
                ]);
                $data = $res->getBody()->getContents();

                $payload = json_encode([
                    'error' => false,
                    'code' => $response->getStatusCode(),
                    'message' => 'OK',
                    'data' => json_decode($data)
                ]);
            } catch (\Exception $e) {
                $payload = json_encode([
                    'error' => true,
                    'code' => $response->getStatusCode(),
                    'message' => $e->getMessage(),
                    'data' => []
                ]);
            }

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/albums', ListAlbumAction::class);
    });
};
