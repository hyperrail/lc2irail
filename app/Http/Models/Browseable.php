<?php

namespace App\Http\Models;


trait Browseable
{

    private $nextUri;
    private $previousUri;
    private $currentUri;

    public function createBrowseable(
        string $previousUri,
        string $currentUri,
        string $nextUri
    )
    {
        $this->previousUri = $previousUri;
        $this->currentUri = $currentUri;
        $this->nextUri = $nextUri;
    }

    /**
     * @return mixed
     */
    public function getNextUri()
    {
        return $this->nextUri;
    }

    /**
     * @return mixed
     */
    public function getCurrentUri()
    {
        return $this->currentUri;
    }

    /**
     * @return mixed
     */
    public function getPreviousUri()
    {
        return $this->previousUri;
    }

}