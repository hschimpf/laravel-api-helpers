<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API\Actions;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginateResults {

    public static string $perPage = 'perPage';

    public static string $all = 'all';

    public function __construct(
        private Request $request,
    ) {}

    public static function definePerPageParameterName(string $perPage): void {
        self::$perPage = $perPage;
    }

    public static function defineShowAllParameterName(string $all): void {
        self::$all = $all;
    }

    public function handle(Builder $query, Closure $next): Collection | LengthAwarePaginator {
        $perPage = $this->request->integer(self::$perPage, $query->getModel()->getPerPage());

        return $next($this->request->boolean(self::$all)
            ? $query->get()
            : $query->paginate($perPage)->withQueryString()
        );
    }

}
