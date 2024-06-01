<?php

namespace App\Services;

use Elasticsearch\ClientBuilder;
use App\Indexing\Documents\Document;
use App\Indexing\Searches\Search;

class IndexingService{

    private $client = null;

    public function __construct(){
        $this->client = ClientBuilder::create()
            ->setHosts([config('elasticsearch.host').":".config('elasticsearch.port')])
            ->build();
    }

    public function setHandler($handler){
        $this->client = ClientBuilder::create()
            ->setHandler($handler)
            ->setHosts([config('elasticsearch.host').":".config('elasticsearch.port')])
            ->build();
    }

    public function search(Search $search,$opts = []){
        return $this->client->search([
            'index' => array_get($opts,'index',config('elasticsearch.index')),
            'type'  => array_get($opts,'type' ,config('elasticsearch.mapping_type')),
            'size'  => array_get($opts,'size' ,config('elasticsearch.results_size')),
            'body'  => $search->toQuery()
        ]);
    }

    public function index(Document $doc,$opts = []){
        $props = [
            'index' => array_get($opts,'index',config('elasticsearch.index')),
            'type'  => array_get($opts,'type' ,config('elasticsearch.mapping_type')),
            'id'    => $doc->getId(),
            'pipeline' => 'attachment',
            'body'  => (array) $doc
        ];

        return $this->client->index($props);
    }

    // Unsafe ! Will cause exception if bulk size is over 10MB
    public function indexBulk(array $docs,$opts = []){
        $params = ['pipeline' => 'attachment', 'body' => []];
        foreach ($docs as $doc) {
            $params['body'][] = [
                'index' => [
                    '_index' => array_get($opts,'index',config('elasticsearch.index')),
                    '_type'  => array_get($opts,'type' ,config('elasticsearch.mapping_type')),
                    '_id'    => $doc->getId()
                ]
            ];

            $params['body'][] = (array) $doc;
        }
        try{
            return $this->client->bulk($params);
        } catch (\Exception $e){
            \Log::error('Caught exception: '.$e->getMessage()."\n");
            return NULL;
        }
    }

    public function remove(array $query,$opts = []){
        return $this->client->deleteByQuery([
            'index'     => array_get($opts,'index',config('elasticsearch.index')),
            'type'      => array_get($opts,'type' ,config('elasticsearch.mapping_type')),
            'body'      => compact('query'),
            'conflicts' => 'proceed'
        ]);
    }

    public function getClient(){
        return $this->client;
    }

}