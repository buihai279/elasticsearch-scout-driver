<?php

namespace JeroenG\Explorer\Infrastructure\Scout\Mixins;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Pagination\Paginator;
use Laravel\Scout\Contracts\PaginatesEloquentModels;

class ScoutBuilderMacros implements BaseScoutBuilderMacros
{
    public function getData(): Closure
    {
        return function ($columns) {
            $data = $this->engine()->search($this)->get();

            if($columns !== ['*']) {
                $data = $data->map(fn ($item) => collect($item)->only($columns));
            }

            return $data;
        };
    }

    public function paginateData(): Closure
    {
        return function (array $columns = ['*'], int $perPage = 10, string $pageName = 'page', int $page = 1) {
            $engine = $this->engine();

            if ($engine instanceof PaginatesEloquentModels) {
                return $engine->simplePaginate($this, $perPage, $page)->appends('query', $this->query);
            }

            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            $perPage = $perPage ?: $this->model->getPerPage();

            $results = $engine->paginate($this, $perPage, $page);

            $data = $results->get();

            if($columns !== ['*']) {
                $data = $data->map(fn ($item) => collect($item)->only($columns));
            }

            $paginator = Container::getInstance()->makeWith(Paginator::class, [
                'items' => $data,
                'perPage' => $perPage,
                'currentPage' => $page,
                'options' => [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ],
            ])->hasMorePagesWhen(($perPage * $page) < $engine->getTotalCount($results));

            return $paginator->appends('query', $this->query);
        };
    }
}
