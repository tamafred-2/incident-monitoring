<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

abstract class Controller
{
    /** Return a normal page or only the authorized batch requested by a scroll list. */
    protected function scrollView(string $name, array $data, array $lists): View|JsonResponse
    {
        $view = view($name, $data);
        $list = request()->header('X-Scroll-List');
        if (! $list) {
            return $view;
        }

        abort_unless(isset($lists[$list]), 404);
        $records = $data[$lists[$list]];
        $modalView = 'scroll-lists.'.$list.'-modals';
        $fragments = [
            'html' => view('scroll-lists.'.$list, $data)->render(),
            'modals' => view()->exists($modalView) ? view($modalView, $data)->render() : '',
        ];

        return response()->json($fragments + [
            'next_url' => $records->nextPageUrl(),
            'count' => $records->count(),
        ]);
    }
}
