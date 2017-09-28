<?php
/**
 * Created by PhpStorm.
 * User: bert
 * Date: 27/09/17
 * Time: 15:02
 */

namespace App\Http\Models;

use Carbon\Carbon;

class LinkedConnectionPage
{
    /**
     * @var LinkedConnection[]
     */
    private $linkedConnections;

    /**
     * @var string
     */
    private $etag;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var Carbon
     */
    private $expiresAt;

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }


    public function __construct(array $linkedConnections, Carbon $createdAt, Carbon $expiresAt, string $etag)
    {
        $this->linkedConnections = $linkedConnections;
        $this->etag = $etag;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return string
     */
    public function getEtag(): string
    {
        return $this->etag;
    }

    /**
     * @return LinkedConnection[]
     */
    public function getLinkedConnections(): array
    {
        return $this->linkedConnections;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getExpiresAt(): \Carbon\Carbon
    {
        return $this->expiresAt;
    }

}