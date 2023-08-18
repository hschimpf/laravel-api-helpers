<?php declare(strict_types=1);

namespace HDSSolutions\Laravel\API;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

abstract class ResourceRelations {

    /**
     * List of relations that are always loaded with the resource
     *
     * @var string[]
     */
    protected array $with = [];

    /**
     * List of counted fields that are always loaded with the resource
     *
     * @var string[]
     */
    protected array $with_count = [];

    /**
     * List of allowed relationships of the resource
     *
     * @var string[]
     */
    protected array $allowed_relations = [];

    final public function __construct(
        protected Request $request,
    ) {}

    final public function handle(Builder $query, Closure $next): Builder | Collection | LengthAwarePaginator {
        // check if query param wasn't defined and just return
        if (null !== $with = $this->request->query('with')) {
            // convert to array if it is a coma separated string
            if (is_string($with) && str_contains($with, ',')) {
                $with = explode(',', $with);
            }

            // must be an array
            if ( !is_array($with)) {
                throw new InvalidArgumentException(
                    message: 'Parameter "with" must be an array.',
                    code:    Response::HTTP_BAD_REQUEST,
                );
            }

            foreach ($this->allowed_relations as $mapping => $relation_name) {
                if (is_int($mapping)) {
                    $mapping = $relation_name;
                }

                // ignore relation if not specified in params
                if ( !in_array($mapping, $with, true)) {
                    continue;
                }

                foreach ((array) $relation_name as $relationship_name) {
                    // check if a method with the relation name exists
                    if (method_exists($this, $method = explode('.', $mapping, 2)[0])) {
                        // redirect relation to the custom method implementation
                        $this->with[$relation_name] = fn(Relation $relation) => $this->$method($relation);
                    } else {
                        $this->with[] = $relationship_name;
                    }
                }
            }
        }

        $this->parseWiths();
        $this->parseWithCounts();

        // append relations to the query
        $query->with($this->with);
        // append relation counts to the query
        $query->withCount($this->with_count);

        return $next($query);
    }

    private function parseWiths(): void {
        $with = [];
        foreach ($this->with as $idx => $relation) {
            // check if the relation is a custom method implementation or isn't a ...
            if ($relation instanceof Closure || !method_exists($this, $method = lcfirst(str($relation)->studly()->toString()))) {
                $with[$idx] = $relation;
                continue;
            }

            // add the relation through the custom method implementation
            $with[$relation] = fn($query) => $this->$method($query);
        }

        // store the parsed relations
        $this->with = $with;
    }

    private function parseWithCounts(): void {
        $with_count = [];
        foreach ($this->with_count as $countable) {
            // check ...
            if ( !method_exists($this, $method = lcfirst(str("{$countable}_count")->studly()->toString()))) {
                $with_count[] = $countable;
                continue;
            }

            // add the relation count through the custom method implementation
            $with_count[$countable] = fn($query) => $this->$method($query);
        }

        // store the parsed relationship counts
        $this->with_count = $with_count;
    }

}
