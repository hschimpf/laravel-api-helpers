<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;

abstract class ResourceFilters {

    /**
     * Available filter operators
     *
     * @var array{ string, string }
     */
    private const OPERATORS = [
        'eq'  => '=',
        'lt'  => '<',
        'lte' => '<=',
        'gt'  => '>',
        'gte' => '>=',
        'ne'  => '!=',
        'has' => 'like',
        'in'  => 'in',
    ];

    /**
     * Available field types with their default operators
     *
     * @var array{ string, string[] }
     */
    private const TYPES = [
        'string'  => [ 'eq', 'ne', 'has' ],
        'numeric' => [ 'eq', 'ne', 'lt', 'lte', 'gt', 'gte', 'in' ],
        'boolean' => [ 'eq', 'ne' ],
        'date'    => [ 'eq', 'ne', 'lt', 'lte', 'gt', 'gte' ],
    ];

    /**
     * List of allowed filtrable columns, with their allowed filter operators
     *
     * @var array{ string, string | string[] }
     */
    protected array $allowed_columns = [];

    /**
     * List of column mappings
     *
     * @var array{ string, string }
     */
    protected array $column_mappings = [];

    final public function __construct(
        protected Request $request,
    ) {}

    final public function handle(Builder $query, Closure $next): Builder | Collection | LengthAwarePaginator {
        foreach ($this->allowed_columns as $column => $operators) {
            // ignore filter if not specified in params
            if (is_null($param = $this->request->query($column))) {
                continue;
            }

            if (is_string($param)) {
                // force parameter without an operator to behave as equal filter
                $param = [ 'eq' => $param ];
            }

            if (is_string($operators)) {
                // validate that field type exists
                if ( !array_key_exists($operators, ResourceFilters::TYPES)) {
                    throw new RuntimeException(
                        message: sprintf('Invalid "%s" field type', $operators),
                        code:    Response::HTTP_INTERNAL_SERVER_ERROR,
                    );
                }

                // load operators for specified field type
                $operators = ResourceFilters::TYPES[ $operators ];
            }

            foreach ($operators as $operator) {
                // ignore operator if not specified in filter param
                if ( !array_key_exists($operator, $param)) {
                    continue;
                }

                // validate that operator is valid
                if ( !array_key_exists($operator, ResourceFilters::OPERATORS)) {
                    throw new RuntimeException(
                        message: sprintf('Invalid "%s" operator', $operator),
                        code:    Response::HTTP_BAD_REQUEST,
                    );
                }

                $this->addQueryFilter($query, $column, $operator, $param[ $operator ]);
            }
        }

        return $next($query);
    }

    private function addQueryFilter(Builder $query, string $column, string $operator, $value): void {
        // check if a method with the param name exists
        if (method_exists($this, $method = lcfirst((string) str($column)->studly()))) {
            // redirect filtering to the custom method implementation
            $query->where(fn($query) => $this->$method(
                $query,
                ResourceFilters::OPERATORS[ $operator ],
                $this->parseValue($operator, $value),
                $value,
            ));

        // capture special case for WHERE IN
        } elseif ($operator === 'in') {
            $query->whereIn(
                column: $this->column_mappings[ $column ] ?? $column,
                values: $this->parseValue($operator, $value),
            );

        } else {
            $query->where(
                column:   $this->column_mappings[ $column ] ?? $column,
                operator: ResourceFilters::OPERATORS[ $operator ],
                value:    $this->parseValue($operator, $value),
            );
        }
    }

    private function parseValue(string $operator, $value) {
        if ($operator === 'eq' && in_array($value, [ 'true', 'false' ], true)) {
            return $value === 'true';
        }

        if ($operator === 'has') {
            return "%$value%";
        }

        if ($operator === 'in' && !is_array($value)) {
            return explode(',', $value);
        }

        return $value;
    }

}
