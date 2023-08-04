<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API\Actions;

use Closure;
use HDSSolutions\Laravel\API\Contracts\ResourceRequest;
use Illuminate\Database\Eloquent\Builder;

final class PaginateResults {

    public function __construct(
        private ResourceRequest $request,
    ) {}

    public function handle(Builder $query, Closure $next): void {
        $next($this->request->boolean('all')
            ? $query->get()
            : $query->paginate()->withQueryString()
        );
    }

}
