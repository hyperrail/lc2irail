<?php
/**
 * Created by PhpStorm.
 * User: bert
 * Date: 07/11/17
 * Time: 17:04
 */

namespace App\Http\Models;


use Carbon\Carbon;

trait ApiResponse
{
    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var Carbon
     */
    private $expiresAt;
    private $etag;

    public function createApiResponse(
        Carbon $createdAt,
        Carbon $expiresAt,
        string $etag
    ) {
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->etag = $etag;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * @return Carbon
     */
    public function getExpiresAt(): Carbon
    {
        return $this->expiresAt;
    }

    /**
     * @return string
     */
    public function getEtag(): string
    {
        return $this->etag;
    }

}