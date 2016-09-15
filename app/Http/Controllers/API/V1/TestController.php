<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;

class TestController extends ApiController
{
    function getAll(){
            return parent::api_response(DummyModel::paginate(10), 'Return paginated posts');
        }

        function getById(){
            return parent::api_response(DummyModel::findOrFail(Input::get('id')), 'Return selected post');
        }

        function search(){
            if($term = Input::get('term')){
                $posts = DummyModel::search($term);
            }else{
                $term = null;
                $posts = DummyModel::query();
            }

            if($sort = Input::get('sort')){
                switch ($sort){
                    case 'newest_oldest':
                        $posts = $posts->orderSearch('created_at', 'desc', $term );
                        break;
                    case 'oldest_newest':
                        $posts = $posts->orderSearch('created_at', 'asc', $term);
                        break;
                }
            }
            return parent::api_response($posts->paginate($this->page_limit), 'Return posts search for '.$term);
        }
}
