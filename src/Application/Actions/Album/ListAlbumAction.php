<?php

declare(strict_types=1);

namespace App\Application\Actions\Album;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use App\Application\Actions\Action;
use App\Exception\MissingArtistException;
use Psr\Http\Message\ResponseInterface as Response;

class ListAlbumAction extends Action
{
    protected $endpoint = 'https://api.spotify.com/v1/';
    protected $token;

    public function __construct(LoggerInterface $logger)
    {
        $this->token = $this->getToken();

        parent::__construct($logger);
    }

    protected function getToken()
    {
        $clientId       = $_ENV['CLIENT_ID'];
        $clientSecret   = $_ENV['CLIENT_SECRET'];

        $credentials = base64_encode($clientId . ':' . $clientSecret);
        $data = [];
        $token = null;

        try {
            $client = new Client([
                'base_uri' => 'https://accounts.spotify.com/api/',
                'verify' => false
            ]);
            $res = $client->request('POST', 'token', [
                'headers' => [
                    'Authorization' => "Basic $credentials"
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);
            $data = json_decode($res->getBody()->getContents());

            $token = $data->access_token;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $token;
    }
    /**
     * get artists id by edpoint spotify
     *
     * @return void
     */
    protected function getArtistId()
    {
        $request = $this->request;
        $id = null;
        $artist = null;
        $params = $request->getQueryParams();

        if (array_key_exists('q', $params)) {
            $artist = $params['q'];
        }

        try {
            $client = new Client([
                'base_uri' => $this->endpoint,
                'verify' => false
            ]);
            $res = $client->request('GET', 'search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token
                ],
                'query' => [
                    'q' => $artist,
                    'type' => 'artist'
                ]
            ]);
            $data = json_decode($res->getBody()->getContents());

            if (!empty($data->artists->items)) {
                $id = $data->artists->items[0]->id;
            } else {
                throw new MissingArtistException('No se encontro ningun artista');
            }
        } catch (MissingArtistException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

        return $id;
    }

    protected function getAllDiscography($idArtist = null)
    {
        $data   = [];
        $albums = [];

        try {
            $client = new Client([
                'base_uri' => $this->endpoint,
                'verify' => false
            ]);
            $res = $client->request('GET', "artists/$idArtist/albums", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token
                ],
                'query' => [
                    'include_groups' => 'album'
                ]
            ]);
            $data = json_decode($res->getBody()->getContents());
            $albums = $data->items;
        } catch (\Exception $e) {
        }

        return $albums;
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $payload    = [];
        $artistId   = $this->getArtistId();
        $albums     = $this->getAllDiscography($artistId);

        if (!empty($albums)) {
            foreach ($albums as $album) {
                $payload[] = [
                    'name'      => $album->name,
                    'released'  => $album->release_date,
                    'tracks'    => $album->total_tracks,
                    'cover'     => $album->images[0]
                ];
            }
        }

        return $this->respondWithData($payload);
    }
}
