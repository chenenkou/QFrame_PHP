<?php
/**
 * ElasticSearch In PHP
 * http://www.elasticsearch.com/docs/elasticsearch/rest_api/
 * ElasticSearch 权威指南 - http://www.learnes.net/
 * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 关系数据库      ⇒ 数据库  ⇒ 表   ⇒ 行    ⇒ 列(Columns)
 * ElasticSearch  ⇒ 索引   ⇒ 类型  ⇒ 文档  ⇒ 字段(Fields)
 * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
class ElasticSearch {
    public $index;
    public $size = 20;

    function __construct($server = 'http://localhost:9200'){
        $this->server = $server;
    }

    function call($path, $http = array()){
        if (!$this->index) throw new Exception('$this->index needs a value');
        return json_decode(file_get_contents($this->server . '/' . $this->index . '/' . $path, NULL, stream_context_create(array('http' => $http))));
    }

    //curl -X PUT http://localhost:9200/{INDEX}/
    function create(){
        $this->call(NULL, array('method' => 'PUT'));
    }

    //curl -X DELETE http://localhost:9200/{INDEX}/
    function drop(){
        $this->call(NULL, array('method' => 'DELETE'));
    }

    //curl -X GET http://localhost:9200/{INDEX}/_status
    function status(){
        return $this->call('_status');
    }

    //curl -X GET http://localhost:9200/{INDEX}/{TYPE}/_count -d {matchAll:{}}
    function count($type){
        return $this->call($type . '/_count', array('method' => 'GET', 'content' => '{ matchAll:{} }'));
    }

    //curl -X PUT http://localhost:9200/{INDEX}/{TYPE}/_mapping -d ...
    function map($type, $data){
        return $this->call($type . '/_mapping', array('method' => 'PUT', 'content' => $data));
    }

    //curl -X PUT http://localhost:9200/{INDEX}/{TYPE}/{ID} -d ...
    function add($type, $id, $data){
        return $this->call($type . '/' . $id, array('method' => 'PUT', 'content' => $data));
    }

    //curl -X GET http://localhost:9200/{INDEX}/{TYPE}/_search?q= ...
    function query($type, $q){
        return $this->call($type . "/_search?" . http_build_query(array('q' => $q)));
    }

    //curl -X GET http://localhost:9200/{INDEX}/{TYPE}/_search??size=5&from=10&q= ...
    function search($type, $q, $page = 1){
        $from = $page - 1;
        return $this->call($type . "/_search?size={$this->size}&from={$from}&" . http_build_query(array('q' => $q)));
    }
}
