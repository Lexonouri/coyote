<?php

namespace Coyote\Elasticsearch;

use Illuminate\Contracts\Support\Arrayable;

trait Elasticsearch
{
    /**
     * Index data in elasticsearch
     *
     * @return mixed
     */
    public function putToIndex()
    {
        $params = $this->getParams();
        $params['body'] = $this->buildArray($this->getBody());

        return $this->getClient()->index($params);
    }

    /**
     * Delete document from index
     *
     * @return mixed
     */
    public function deleteFromIndex()
    {
        return $this->getClient()->delete($this->getParams());
    }

    /**
     * @param $body
     * @return Response
     */
    public function search($body)
    {
        $params = $this->getParams();
        $params['body'] = $body;

        return $this->getResponse($this->getClient()->search($params));
    }

    /**
     * @param $response
     * @return Response
     */
    protected function getResponse($response)
    {
        return new Response($response);
    }

    /**
     * Basic elasticsearch params
     *
     * @return array
     */
    protected function getParams()
    {
        $params = [
            'index' => $this->getIndexName(),
            'type' => $this->getTable()
        ];

        if ($this->getKey()) {
            $params['id'] = $this->getKey();
        }

        return $params;
    }

    /**
     * Convert model to array
     *
     * @param $collection
     * @return mixed
     */
    protected function buildArray($collection)
    {
        if (is_object($collection) && $collection instanceof Arrayable) {
            $collection = $collection->toArray();
        }

        foreach ($collection as &$value) {
            if (is_object($value) && $collection instanceof Arrayable) {
                $value = $this->buildArray($value);
            }
        }

        return $collection;
    }

    /**
     * Get client instance
     *
     * @return \Illuminate\Foundation\Application|mixed
     */
    protected function getClient()
    {
        return app('Elasticsearch');
    }

    /**
     * Get default index name from config
     *
     * @return mixed
     */
    protected function getIndexName()
    {
        return config('elasticsearch.default_index');
    }

    /**
     * Default data to index in elasticsearch
     *
     * @return mixed
     */
    protected function getBody()
    {
        return $this->toArray();
    }
}