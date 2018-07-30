<?php

namespace App\Http\Controllers;

use App\{
    Models\Airport,
    Models\Booking,
    Models\Event,
    Exports\BookingsExport,
    Http\Requests\AdminAutoAssign,
    Http\Requests\AdminUpdateBooking,
    Http\Requests\StoreBooking,
    Http\Requests\UpdateBooking,
    Mail\BookingCancelled,
    Mail\BookingChanged,
    Mail\BookingConfirmed,
    Mail\BookingDeleted
};
use Carbon\Carbon;
use Illuminate\{
    Http\Request, Support\Facades\Auth, Support\Facades\Mail
};

class BookingController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth.isLoggedIn')->except('index');

        $this->middleware('auth.isAdmin')->only(['create', 'store', 'destroy', 'adminEdit', 'adminUpdate', 'export', 'adminAutoAssignForm', 'adminAutoAssign']);
    }

    /**
     * Display a listing of the bookings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->removeOverdueReservations();

        $event = Event::query()->where('endEvent', '>', Carbon::now())->orderBy('startEvent', 'asc')->first();
        $bookings = collect();

        if($event)
            $bookings = Booking::where('event_id', 1)->orderBy('ctot')->get();

        return view('booking.overview', compact('event', 'bookings'));
    }

    public function removeOverdueReservations()
    {
        // Get all reservations that have been reserved
        foreach (Booking::with('reservedBy')->get() as $booking) {
            // If a reservation has been reserved for more then 10 minutes, remove reservedBy_id
            if (Carbon::now() > Carbon::createFromFormat('Y-m-d H:i:s', $booking->updated_at)->addMinutes(10)) {
                $booking->fill([
                    'reservedBy_id' => null,
                    'updated_at' => NOW(),
                ]);
                $booking->save();
            }
        }
    }

    /**
     * Show the form for creating new timeslots
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $event = Event::whereKey($request->id)->first();
        $airports = Airport::all();

        return view('booking.create', compact('event', 'airports'));
    }

    /**
     * Store new timeslots in storage.
     *
     * @param StoreBooking $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBooking $request)
    {
        $event = Event::whereKey($request->id)->first();
        $from = Airport::findOrFail($request->from);
        $to = Airport::findOrFail($request->to);
        $event_start = Carbon::createFromFormat('Y-m-d H:i', $event->startEvent->toDateString() . ' ' . $request->start);
        $event_end = Carbon::createFromFormat('Y-m-d H:i', $event->endEvent->toDateString() . ' ' . $request->end);
        $separation = $request->separation;
        $count = 0;
        for ($event_start; $event_start <= $event_end; $event_start->addMinutes($separation)) {
            if (!Booking::where([
                'event_id' => $request->id,
                'ctot' => $event_start,
            ])->first()) {
                Booking::create([
                    'event_id' => $request->id,
                    'dep' => $from->icao,
                    'arr' => $to->icao,
                    'ctot' => $event_start,
                ])->save();
                $count++;
            }
        }
        flashMessage('success','Done',$count.' Slots have been created!');
        return redirect('/booking');
    }

    /**
     * Display the specified booking.
     *
     * @param Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function show(Booking $booking)
    {
        if (Auth::check() && Auth::id() == $booking->bookedBy_id || Auth::user()->isAdmin) {
            return view('booking.show', compact('booking'));
        } else {
            flashMessage('danger', 'Already booked', 'Whoops that booking belongs to somebody else!');
            return redirect('/booking');
        }
    }

    /**
     * Show the form for editing the specified booking.
     *
     * @param Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function edit(Booking $booking)
    {
        // Check if the booking has already been booked or reserved
        if (isset($booking->bookedBy_id) || isset($booking->reservedBy_id)) {
            // Check if current user has booked/reserved
            if ($booking->bookedBy_id == Auth::id() || $booking->reservedBy_id == Auth::id()) {
                if ($booking->reservedBy_id == Auth::id()) {
                    flashMessage('info', 'Slot reserved', 'Will remain reserved until ' . $booking->updated_at->addMinutes(10)->format('Hi') . 'z');
                }
                return view('booking.edit', compact('booking', 'user'));
            } else {
                // Check if the booking has already been reserved
                if (isset($booking->reservedBy_id)) {
                    flashMessage('danger', 'Warning', 'Whoops! Somebody else reserved that slot just before you! Please choose another one. The slot will become available if it isn\'t confirmed within 10 minutes.');
                    return redirect('/booking');

                } // In case the booking has already been booked
                else {
                    flashMessage('danger', 'Warning', 'Whoops! Somebody else booked that slot just before you! Please choose another one.');
                    return redirect('/booking');
                }
            }
        } // If the booking hasn't been taken by anybody else, check if user doesn't already have a booking
        else {
            if (Auth::user()->booked()->where('event_id', $booking->event_id)->first()) {
                flashMessage('danger', 'Nope!', 'You already have a booking!');
                return redirect('/booking');
            }
            // If user already has another reservation open
            if (Auth::user()->reserved()->where('event_id', $booking->event_id)->first()) {
                flashMessage('danger!', 'Nope!', 'You already have a reservation!');
                return redirect('/booking');
            } // Reserve booking, and redirect to booking.edit
            else {
                // Check if you are allowed to reserve the slot
                if ($booking->event->startBooking < now()) {
                    if ($booking->event->endBooking > now()) {
                        $booking->reservedBy_id = Auth::id();
                        $booking->save();
                        flashMessage('info', 'Slot reserved', 'Will remain reserved until ' . $booking->updated_at->addMinutes(10)->format('Hi') . 'z');
                        return view('booking.edit', compact('booking', 'user'));
                    }
                    else {
                        flashMessage('danger', 'Nope!', 'Bookings have been closed at ' . $booking->event->endBooking->format('d-m-Y Hi') . 'z');
                        return redirect('/booking');
                    }
                }
                else {
                    flashMessage('danger', 'Nope!', 'Bookings aren\'t open yet. They will open at ' . $booking->event->startBooking->format('d-m-Y Hi') . 'z');
                    return redirect('/booking');
                }
            }
        }
    }

    /**
     * Update the specified booking in storage.
     *
     * @param UpdateBooking $request
     * @param Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBooking $request, Booking $booking)
    {
        // Check if the reservation / booking actually belongs to the correct person
        if (Auth::id() == $booking->reservedBy_id || Auth::id() == $booking->bookedBy_id) {
            $this->validateSELCAL($request->selcal1, $request->selcal2, $booking->event_id);
            if (!empty($request->selcal1) && !empty($request->selcal2)) {
                $this->validateSELCAL($request->selcal1, $request->selcal2, $booking->event_id);
                $selcal = $request->selcal1 . '-' . $request->selcal2;
            }
            $booking->fill([
                'reservedBy_id' => null,
                'bookedBy_id' => Auth::id(),
                'callsign' => $request->callsign,
                'acType' => $request->aircraft,
            ]);
            if (isset($selcal)) {
                $booking->selcal = $selcal;
            }
            $booking->save();
            Mail::to(Auth::user())->send(new BookingConfirmed($booking));
            flashMessage('success', 'Booking created!', 'Booking has been created! A E-mail with details has also been sent');
            return redirect('/booking');
        } else {
            if ($booking->reservedBy_id != null) {
                // We got a bad-ass over here, log that person out
                Auth::logout();
                return redirect('https://youtu.be/dQw4w9WgXcQ');
            }
            else {
                flashMessage('warning', 'Nope!', 'That reservation does not belong to you!');
                return redirect('/booking');
            }
        }
    }

    public function validateSELCAL($a, $b, $eventId)
    {
        $selcal = $a . '-' . $b;
        $bookings = Booking::where('event_id', $eventId)->get();
        foreach ($bookings as $booking) {
            if ($booking->selcal == $selcal) {
                return false;
            }
        }
        return $a . '-' . $b;
    }

    /**
     * Remove the specified booking from storage.
     *
     * @param Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        flashMessage('success', 'Booking deleted!', 'Booking has been deleted');
        if (!empty($booking->bookedBy_id)) {
            Mail::to(Auth::user())->send(new BookingDeleted($booking->event, $booking->bookedBy));
        }
        return redirect('/booking');
    }

    /**
     * Sets reservedBy and bookedBy to null.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);
        if (Auth::id() == $booking->reservedBy_id || Auth::id() == $booking->bookedBy_id) {
            if ($booking->event->endBooking > now()) {
                $booking->fill([
                    'reservedBy_id' => null,
                    'bookedBy_id' => null,
                    'callsign' => null,
                    'acType' => null,
                    'selcal' => null,
                ]);
                if (Auth::id() == $booking->getOriginal('bookedBy_id')) {
                    $title = 'Booking removed!';
                    $message = 'Booking has been removed! A E-mail has also been sent';
                    Mail::to(Auth::user())->send(new BookingCancelled($booking->event, Auth::user()));
                } else {
                    $title = 'Slot free';
                    $message = 'Slot is now free to use again';
                }
                flashMessage('info', $title, $message);
                $booking->save();
                return redirect('/booking');
            }
            flashMessage('danger', 'Nope!', 'Bookings have been locked at ' . $booking->event->endBooking->format('d-m-Y Hi') . 'z');

        } else {
            // We got a bad-ass over here, log that person out
            Auth::logout();
            return redirect('https://youtu.be/dQw4w9WgXcQ');
        }
    }

    /**
     * Show the form for editing the specified booking.
     * @param Booking $booking
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function adminEdit(Booking $booking)
    {
        $airports = Airport::all();

        return view('booking.admin.edit', compact('booking', 'airports'));
    }

    /**
     * Updates booking.
     *
     * @param AdminUpdateBooking $request
     * @param Booking $booking
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function adminUpdate(AdminUpdateBooking $request, Booking $booking)
    {
        $booking->fill([
            'callsign' => $request->callsign,
            'ctot' => Carbon::createFromFormat('Y-m-d H:i', $booking->event->startEvent->toDateString() . ' ' . $request->ctot),
            'dep' => $request->ADEP,
            'arr' => $request->ADES,
            'route' => $request->route,
            'oceanicFL' => $request->oceanicFL,
            'oceanicTrack' => $request->oceanicTrack,
            'acType' => $request->aircraft,
        ]);
        $changes = collect();
        if (!empty($booking->bookedBy)) {
            foreach ($booking->getDirty() as $key => $value) {
                $changes->push(
                    ['name' => $key, 'old' => $booking->getOriginal($key), 'new' => $value]
                );
            }
        }
        if (!empty($request->message)) {
            $changes->push(
                ['name' => 'message', 'new' => $request->message]
            );
        }
        $booking->save();
        if (!empty($booking->bookedBy)) {
            Mail::to($booking->bookedBy->email)->send(new BookingChanged($booking, $changes));
        }
        $message = 'Booking has been changed!';
        if (!empty($booking->bookedBy)) {
            $message .= ' A E-mail has also been sent to the person that booked.';
        }
        flashMessage('success', 'Booking changed', $message);
        return redirect('/booking');
    }

    /**
     * Exports all active bookings to a .csv file
     *
     * @param int $id
     * @return BookingsExport
     */
    public function export($id)
    {
        return new BookingsExport($id);
    }

    public function adminAutoAssignForm($id)
    {
        $event = Event::whereKey($id)->first();
        return view('event.admin.autoAssign', compact('event'));
    }

    public function adminAutoAssign(AdminAutoAssign $request, $id)
    {
        $event = Event::find($id);
        $bookings = Booking::where('event_id',$event->id)
            ->whereNotNull('bookedBy_id')
            ->orderBy('ctot')
            ->get();
        $count = 0;
        $flOdd = $request->maxFL;
        $flEven = $request->minFL;
        foreach ($bookings as $booking) {
            $count++;
            if ($count % 2 == 0) {
                $booking->fill([
                    'oceanicTrack' => $request->oceanicTrack2,
                    'route' => $request->route2,
                    'oceanicFL' => $flEven,
                ]);
                $flEven = $flEven + 10;
                if ($flEven > $request->maxFL) {
                    $flEven = $request->minFL;
                }
            } else {
                $booking->fill([
                    'oceanicTrack' => $request->oceanicTrack1,
                    'route' => $request->route1,
                    'oceanicFL' => $flOdd,
                ]);
                $flOdd = $flOdd - 10;
                if ($flOdd < $request->minFL) {
                    $flOdd = $request->maxFL;
                }
            }
            $booking->save();

        }
        flashMessage('success', 'Bookings changed', $count. ' Bookings have been Auto-Assigned a FL, and route');
        return redirect('/admin/event');
    }
}
