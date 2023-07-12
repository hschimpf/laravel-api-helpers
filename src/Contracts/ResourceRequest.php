<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API\Contracts;

interface ResourceRequest {

    /**
     * Builds a hash identifier for the request
     *
     * @param  ?string  $append
     *
     * @return string Request hashed identifier
     */
    public function hash(string $append = null): string;

    public function authorize(): bool;

    public function rules(): array;

}
