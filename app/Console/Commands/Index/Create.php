<?php

namespace App\Console\Commands\Index;

use Illuminate\Console\Command;
use App\Services\IndexingService;

class Create extends Command
{

    protected $signature = 'index:create';
    protected $description = 'Initializes the elasticsearch index';
    protected $service;

    public function __construct(IndexingService $service){
        parent::__construct();
        $this->service = $service;
    }

    public function handle(){
        $this->createPipeline();
        $this->createIndex();
    }

    private function createPipeline(){
        $params = [
            'id' => 'attachment',
            'body' => [
                'description' => 'Extract attachment information',
                'processors' => [
                    [
                        'attachment' => [
                            'field' => 'data'
                        ],
                        'remove' => [
                            'field' => 'data'
                        ]
                    ]
                ]
            ]
        ];
        $this->service->getClient()->ingest()->putPipeline($params);
        $this->info('Pipeline created');
    }

    private function createIndex(){
        $options = [
            'index' => config('elasticsearch.index'),
            'body'  => [
                "mappings" => [
                    config('elasticsearch.mapping_type') => [
                        "_all" => [
                            "enabled" => false
                        ],
                        "properties" => [
                            "title" => [
                                "type"            => "text",
                                "analyzer"        => "autocomplete", // Analyze the title in normalized n-grams
                                "search_analyzer" =>  "search"
                            ],
                            "tags" => [
                                "type"            => "text",
                                "analyzer"        => "autocomplete", //Tags too
                                "search_analyzer" =>  "search"
                            ],
                            "data" => [
                                "type" => "text",
                                "analyzer"        => "fulltext",
                                "search_analyzer" =>  "fulltext"
                            ],
                            "attachment.content" => [
                                "type" => "text",
                            ],
                            "shareable_type" => [
                                "type" => "keyword"
                            ],
                            "shareable_id" => [
                                "type" => "long"
                            ],
                            "user_id" => [
                                "type" => "long"
                            ],
                            "team_id" => [
                                "type" => "long"
                            ],
                            "folder_id" => [
                                "type" => "long"
                            ],
                            "share_id" => [
                                "type" => "long"
                            ],
                            "type_meta" => [
                                "type" => "keyword"
                            ],
                            "last_modified" => [
                                "type" => "date",
                                "format" => "yyyy-MM-dd HH:mm:ss"
                            ],
                            "path" => [
                                "type" => "keyword"
                            ]
                        ]
                    ]
                ],
                "settings" => [
                    "index.query.default_field" => "title",
                    "analysis" => [
                        "analyzer" => [
                            "autocomplete" => [
                                "type"        => "custom",
                                "tokenizer"   => "standard",
                                "filter"      => [
                                    "word_delimiter",
                                    "asciifolding",
                                    "lowercase",
                                    "autocomplete_filter"
                                ]
                            ],
                            "fulltext" => [
                                "type"        => "custom",
                                "tokenizer"   => "standard",
                                "char_filter" => [ "html_strip" ],
                                "filter"      => [
                                    "word_delimiter",
                                    "asciifolding",
                                    "lowercase",
                                    "english_stop",
                                    "english_stemmer",
                                ]
                            ],
                            "search" => [
                                "type"        => "custom",
                                "tokenizer"   => "standard",
                                "filter"      => [
                                    "word_delimiter",
                                    "asciifolding",
                                    "lowercase",
                                ]
                            ]
                        ],
                        "filter" => [
                            "english_stemmer" => ['type' => 'stemmer', 'language'  => 'english'],
                            "english_stop"    => ['type' => 'stop'   , 'stopwords' => '_english_'],
                            "autocomplete_filter" => [
                                "type"     => "edgeNGram",
                                "min_gram" => 2,
                                "max_gram" => 20
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $this->service->getClient()->indices()->create($options);
        $this->info("Index created");
    }
}
