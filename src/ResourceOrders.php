<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

abstract class ResourceOrders {

    /**
     * Default order when no one specified in the request
     *
     * @var string[]
     */
    protected array $default_order = [];

    /**
     * List of allowed columns to sort the resource collection
     *
     * @var string[]
     */
    protected array $allowed_columns = [];

    final public function __construct(
        protected Request $request,
    ) {}

    final public function handle(Builder $query, Closure $next): void {
        // check if query param was not defined
        if (null === $order = $this->request->query('order')) {
            // add default sorting fields
            $this->setDefaultOrder($query);

            $next($query);

            return;
        }

        // must follow the syntax order[{index}][{direction}]={field}
        if ( !is_array($order)) {
            throw new InvalidArgumentException(
                message: 'Order parameter must have a numeric index, a direction and a field, example: order[0][asc]=field_name',
                code:    Response::HTTP_BAD_REQUEST,
            );
        }

        $this->clean($order);

        foreach ($order as $value) {
            // use only the first order that we found
            $direction = array_key_first($value);

            $this->addQueryOrder($query, $value[$direction], $direction);
        }

        $next($query);
    }

    private function clean(array &$order): void {
        sort($order);

        $available_order_fields = [
            ...array_filter(array_keys($this->allowed_columns), 'is_string'),
            ...array_filter($this->allowed_columns, 'is_int', ARRAY_FILTER_USE_KEY)
        ];
        $cleaned = [];
        $already_added = [];

        foreach ($order as $idx => $value) {
            // validate that order index is an int, direction is either "asc" or "desc", and has a field name
            if ( !is_int($idx) || !in_array($direction = array_key_first($value), [ 'asc', 'desc' ], true) || !is_string($value[$direction])) {
                throw new InvalidArgumentException(
                    message: 'Order parameter must have a numeric index, a direction and a field name, example: order[0][asc]=field_name',
                    code:    Response::HTTP_BAD_REQUEST,
                );
            }

            // check if field name is in the allowed list, and wasn't already added to the order list
            if ( !in_array($column = $value[$direction], $available_order_fields, true) || in_array($value[$direction], $already_added, true)) {
                continue;
            }

            $already_added[] = $column;
            $cleaned[] = $value;
        }

        // store the cleaned orders
        $order = $cleaned;
    }

    private function addQueryOrder(Builder $query, string $column, string $direction): void {
        if (method_exists($this, $method = lcfirst((string) str($column)->studly()))) {
            $this->$method($query, $direction);

        } else {
            $query->orderBy($this->allowed_columns[ $column] ?? $column, $direction);
        }
    }

    private function setDefaultOrder(Builder $query): void {
        foreach ($this->default_order as $column => $direction) {
            // check if key is column name
            $column = is_string($column) ? $column : $direction;
            // set default order if not specified
            $direction = in_array(strtoupper($direction), [ 'ASC', 'DESC' ]) ? strtoupper($direction) : 'ASC';
            // add order by column
            $query->orderBy($column, $direction);
        }
    }

}
