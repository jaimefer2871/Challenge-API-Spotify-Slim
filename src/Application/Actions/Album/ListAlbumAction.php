<?php
declare(strict_types=1);

namespace App\Application\Actions\Album;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Client;

class ListAlbumAction extends Action
{

    protected $endpoint = 'https://api.spotify.com/v1/';
    protected $token = 'BQCR0BeSfAHBV56Esv295k_SXGY75pLZniJUS4KDYIv8tjRSrzxRENdULxZoC5Xkx9PmSPuzGPZMjiPM4lc';

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
            $client = new Client(['base_uri' => $this->endpoint]);
            $res = $client->request('GET', 'search', [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token
                    ],
                    'query' => [
                        'q' => $artist,
                        'type' => 'artist'
                    ]
                ]);
            $data = json_decode($res->getBody()->getContents());
            $id = $data->artists->items[0]->id;
        } catch (\Exception $e) {
            $data = null;
        }

        return $id;
    }

    protected function getAllDiscography($idArtist = null)
    {
        $data = [];
        $albums = [];

        try {
            $client = new Client(['base_uri' => $this->endpoint]);
            $res = $client->request('GET', "artists/$idArtist/albums", [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token
                    ],
                    'query' => [
                        'include_groups' => 'album'
                    ]
                ]);
            $data = json_decode($res->getBody()->getContents());
            $albums = $data->items;
        } catch (\Exception $e) {
            // $data = [];
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

        foreach($albums as $album) {
            $payload[] = [
                'name'      => $album->name,
                'released'  => $album->release_date,
                'tracks'    => $album->total_tracks,
                'cover'     => $album->images[0]
            ];
        }

        return $this->respondWithData($payload);
    }
}
