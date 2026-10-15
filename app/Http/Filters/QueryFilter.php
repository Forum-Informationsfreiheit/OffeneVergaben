<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Kblais\QueryFilter\QueryFilter as BaseQueryFilter;

/**
 * Base class for the filters of this application.
 *
 * kblais/query-filter 1.x kept the request in `$this->request`. In 3.x the base class only
 * keeps the filter values (`$this->filters`, a Collection) and its constructor is final, so
 * the request is captured here instead: `setFiltersFromRequest()` is what the constructor
 * calls when a request is passed, and the controllers/tests always pass one.
 *
 * The filters need the request itself (not only the filter values) for `has()`, for the
 * sort links built by `makeSortUrl()`, and for the cross-field lookups in DatasetFilter
 * (`date_type`, `cpv_like`).
 */
abstract class QueryFilter extends BaseQueryFilter
{
    /**
     * The request the filter values came from, if any.
     *
     * @var Request|null
     */
    protected $filterRequest;

    /**
     * 1.x returned the builder from apply(), 3.x returns nothing. The builder is mutated in
     * place either way, but callers such as SendSubscriptionUpdateNotificationsJob assign the
     * result, so the old contract is kept here.
     *
     * @return Builder
     */
    public function apply(Builder $builder)
    {
        parent::apply($builder);

        return $builder;
    }

    public function setFiltersFromRequest(Request $request): BaseQueryFilter
    {
        $this->filterRequest = $request;

        return parent::setFiltersFromRequest($request);
    }

    /**
     * The request the filter was built from, falling back to the current one.
     *
     * @return Request
     */
    protected function request()
    {
        return $this->filterRequest ?: request();
    }
}
