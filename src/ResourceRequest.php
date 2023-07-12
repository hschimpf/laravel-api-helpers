<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API;

use Illuminate\Foundation\Http\FormRequest;
use RuntimeException;

class ResourceRequest extends FormRequest implements Contracts\ResourceRequest {

    final public function hash(string $append = null): string {
        $parameters = $this->route()?->parameters() ?? [];

        return sprintf('%s %s [%s@%s]',
            $this->method(),
            str_replace(array_map(static fn($key) => sprintf('{%s}', $key), array_keys($parameters)), $parameters, $this->route()?->uri()),
            $this->route()?->getName(),
            substr(md5(preg_replace('/&?cache=[^&]*/', '', $this->getQueryString() ?? '')), 0, 10).($append ? ':'.substr(md5($append), 0, 6) : ''),
        );
    }

    public function authorize(): bool {
        return true;
    }

    final public function rules(): array {
        return match ($this->method()) {
            'GET'          => $this->index(),
            'POST'         => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE'       => $this->destroy(),

            default        => throw new RuntimeException(sprintf('Unsupported method %s', $this->method())),
        };
    }

    protected function index(): array {
        return [];
    }

    protected function store(): array {
        return [];
    }

    protected function update(): array {
        return [];
    }

    protected function destroy(): array {
        return [];
    }

}
