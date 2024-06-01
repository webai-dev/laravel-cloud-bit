<?php

namespace App\Indexing\Searches;

use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;

use Auth;

class TeamShareablesSearch implements Search{

    private $text = '';
    private $query = [];
    private $filters = [];

    public function setText($text){
        if($text === null || strlen($text) === 0) return $this;

        $this->addQuery('match_phrase',  ['title' => [
            'query' => $text,
            'boost' => 4.0
        ]]);
        $this->addQuery('match',  ['title' => [
            'query' => $text,
            'boost' => 3.0
        ]]);
        $this->addQuery('match',  ['tags' => [
            'query' => $text,
            'boost' => 2.0
        ]]);
        $this->addQuery('match_phrase',  ['attachment.content' => [
            'query' => $text,
            'boost' => 2.0
        ]]);
        $this->addQuery('match',  ['attachment.content' => [
            'query' => $text,
            'boost' => 1.0
        ]]);

        return $this;
    }

    public function addTags($tags){
        if ($tags == null || count($tags) == 0)  return $this;

        foreach($tags as $tag){
            $this->addQuery('match', ['tags' => [
                'query' => $tag, 'boost'=> 3
            ]]);
        }
        return $this;
    }

    public function addDate($date){
        if ($date == null) return $this;

        $this->addFilter('range',['gte' => [
            'last_modified' => $date
        ]]);
        return $this;
    }

    public function addOwner($owner_id){
        if ($owner_id == null) return $this;

        $this->addFilter('term',compact('owner_id'));
        return $this;
    }

    public function addUser($user_id){
        $this->addFilter('term',compact('user_id'));
        return $this;
    }

    public function addTeam($team_id){
        $this->addFilter('term',compact('team_id'));
        return $this;
    }

    public function addTypes($types){
        if ($types == null || count($types) == 0)  return $this;

        $terms = [];
        foreach($types as $type){
            $parts = explode(":",$type);
            if (count($parts) == 1) {
                $terms [] = ['term' => ['shareable_type' => $parts[0]]];
            }
            else {
                $terms [] = ['term' => ['type_meta' => $parts[1]]];
            }

        }
        $this->addFilter('bool', ['should' => $terms]);
        return $this;
    }

    public function addSharedWith($user_ids){
        if ($user_ids == null) return $this;

        $this->addFilter('terms',['shared_with' => $user_ids]);
        return $this;
    }

    public function clearFilters(){
        $this->filters = [];
    }

    public function toQuery(){
        if(!$this->hasQuery()) $this->matchAll();

        return [
            'query' => [
                'bool' => [
                    'must'   => $this->query,
                    'filter' => $this->filters,
                ]
            ],
            'highlight' => [
                'fields' => ['title' => new \stdClass(), 'attachment.content' => new \stdClass()]
            ],
        ];
    }

    public function parseResults($results){
        $hits  = $results['hits']['hits'];

        return array_map(function($hit){
            return  [
                'id'         => array_get($hit,'_source.shareable_id'),
                'title'      => array_get($hit,'_source.title'),
                'highlights' => array_get($hit,'highlight',[]),
                'type'       => array_get($hit,'_source.shareable_type'),
                'folder_id'  => array_get($hit,'_source.folder_id'),
            ];
        },$hits);
    }

    public function getInsight($results){
        $hits  = $results['hits']['hits'];

        return array_map(function($hit){
            return  [
                'id'         => array_get($hit,'_source.shareable_id'),
                'title'      => array_get($hit,'_source.title'),
                'score'       => array_get($hit,'_score'),
                'highlights' => array_get($hit,'highlight',[]),
            ];
        },$hits);
    }


    public function parseResultsDetailed($results){
        $parsed = $this->parseResults($results);

        $files = $this->getItemsQuery($parsed,File::class);
        $folders = $this->getItemsQuery($parsed,Folder::class);
        $bits = $this->getItemsQuery($parsed,Bit::class);//->with('type');

        return compact('files','folders','bits');
    }




    protected function getItemsQuery($results,$class){
        $instance = new $class();

        $items = array_filter($results,function($result) use($instance){
            return $result['type'] == $instance->getType();
        });

        $ids = array_pluck($items,'id');

        $query = $class::whereIn('id',$ids)
            ->withLocked(Auth::id())
            ->withCount('shares')
            ->withRenamedTitle(Auth::id());
        if($class == Bit::class) $query = $query->with('type');

        $items = $query->get();

        $ordered_items = [];
        foreach($ids as $id){
            foreach($items as $item){
                if($item->id == $id) {
                    $ordered_items[] = $item;
                    break;
                }
            }
        }
        return $ordered_items;
    }

    protected  function addQuery($type,$value){
        if($this->query === []){
            $this->query = ['bool' => [
                'should' => [],
                "minimum_should_match" => 1
            ]];
        }
        $this->query['bool']['should'][] = [$type => $value];
    }

    protected function hasQuery(){
        return $this->query !== [];
    }

    protected function addFilter($type,$value){
        $this->filters[] = [
            $type => $value
        ];
    }

    protected function matchAll(){
        $this->query = ['match_all' => ['boost' => 1.0]];
    }

}