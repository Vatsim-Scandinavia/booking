<?php

namespace App\Http\Requests\Event\Admin;

use App\Http\Requests\Request;

class StoreEvent extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'is_online' => 'required|boolean',
            'show_on_homepage' => 'required|boolean',
            'name' => 'bail|required:string',
            'event_type_id' => 'exists:event_types,id|required',
            'import_only' => 'required|boolean',
            'uses_times' => 'required|boolean',
            'multiple_bookings_allowed' => 'required|boolean',
            'is_oceanic_event' => 'required|boolean',
            'dateEvent' => 'required|date',
            'dep' => 'exists:airports,id|required',
            'arr' => 'exists:airports,id|required',
            'timeBeginEvent' => 'required',
            'timeEndEvent' => 'required',
            'dateBeginBooking' => 'required|date',
            'timeBeginBooking' => 'required',
            'dateEndBooking' => 'required|date|after_or_equal:dateBeginBooking',
            'timeEndBooking' => 'required',
            'image_url' => 'nullable|url',
            'description' => 'required:string',
        ];
    }
}
