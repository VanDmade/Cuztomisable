<?php

namespace VanDmade\Cuztomisable\Controllers;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\FormoraRequest;
use VanDmade\Cuztomisable\Models\Formora;
use Auth;
use DB;
use Exception;

class FormoraController extends Controller
{

    public function get($page)
    {
        try {
            $form = Formora::where('current', '=', $page)->first();
            if (isset($form->id)) {

            }
            return $this->success([
                'form' => $form,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(FormoraRequest $request, $page)
    {
        try {
            $data = $request->validated();
            $formora = new Formora();
            $formora->to = $data['to'] ?? null;
            $formora->to_params = $data['to_params'] ?? null;
            $formora->current = $data['current'] ?? null;
            $formora->current_params = $data['current_params'] ?? null;
            $formora->form = $data['form'] ?? null;
            $formora->save();
            return $this->success([
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }


}
