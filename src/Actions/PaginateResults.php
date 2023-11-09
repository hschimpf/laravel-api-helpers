<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API\Actions;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginateResults {

    public function __construct(
        private Request $request,
    ) {}

    public function handle(Builder $query, Closure $next): Collection | LengthAwarePaginator {
        $perPage = $this->request->integer('perPage', $query->getModel()->getPerPage());

        return $next($this->request->boolean('all')
            ? $query->get()
            : $query->paginate($perPage)->withQueryString()
        );
    }

}
