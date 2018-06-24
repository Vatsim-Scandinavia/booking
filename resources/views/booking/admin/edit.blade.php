@extends('layouts.app')

@section('content')
    @if (count($errors) > 0)
        <div class = "alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @include('layouts.alert')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ $booking->event->name }} | Edit Booking</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('booking.admin.update',$booking->id) }}">
                        @csrf
                        @method('PATCH')

                        {{--Callsign--}}
                        <div class="form-group row">
                            <label for="callsign" class="col-md-4 col-form-label text-md-right">Callsign</label>

                            <div class="col-md-6">
                                <div class="form-control-plaintext"><b>{{ $booking->callsign ? $booking->callsign : '-' }}</b></div>

                            </div>
                        </div>

                        {{--CTOT--}}
                        <div class="form-group row">
                            <label for="ctot" class="col-md-4 col-form-label text-md-right">CTOT</label>

                            <div class="col-md-6">
                                <input id="ctot" type="time" class="form-control{{ $errors->has('ctot') ? ' is-invalid' : '' }}" name="ctot" value="{{ old('ctot', \Carbon\Carbon::parse($booking->getOriginal('ctot'))->format('H:i')) }}" required autofocus>

                                @if ($errors->has('ctot'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('ctot') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{--ADEP--}}
                        <div class="form-group row">
                            <label for="adep" class="col-md-4 col-form-label text-md-right">ADEP</label>

                            <div class="col-md-6">
                                <div class="form-control-plaintext"><b>{{ $booking->dep }}</b></div>

                            </div>
                        </div>

                        {{--ADES--}}
                        <div class="form-group row">
                            <label for="ades" class="col-md-4 col-form-label text-md-right">ADES</label>

                            <div class="col-md-6">
                                <div class="form-control-plaintext"><b>{{ $booking->arr }}</b></div>

                            </div>
                        </div>

                        {{--PIC--}}
                        <div class="form-group row">
                            <label for="pic" class="col-md-4 col-form-label text-md-right">PIC</label>

                            <div class="col-md-6">
                                <div class="form-control-plaintext"><b>{{ $booking->bookedBy ? $booking->bookedBy->pic : '-' }}</b></div>
                            </div>
                        </div>

                        {{--Route--}}
                        <div class="form-group row">
                            <label for="route" class="col-md-4 col-form-label text-md-right">Route</label>

                            <div class="col-md-6">
                                <textarea class="form-control" id="" name="route">{{ old('route',$booking->route) }}</textarea>

                                @if ($errors->has('ctot'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('ctot') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{--Track--}}
                        <div class="form-group row">
                            <label for="oceanicTrack" class="col-md-4 col-form-label text-md-right">Track</label>

                            <div class="col-md-6">
                                <input id="oceanicTrack" type="text" class="form-control{{ $errors->has('oceanicTrack') ? ' is-invalid' : '' }}" name="oceanicTrack" value="{{ old('oceanicTrack',$booking->getOriginal('oceanicTrack')) }}" max="2">

                                @if ($errors->has('oceanicTrack'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('oceanicTrack') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{--Oceanic Entry FL--}}
                        <div class="form-group row">
                            <label for="oceanicFL" class="col-md-4 col-form-label text-md-right">Oceanic Entry FL</label>

                            <div class="col-md-6">
                                <input id="oceanicFL" type="text" class="form-control{{ $errors->has('oceanicFL') ? ' is-invalid' : '' }}" name="oceanicFL" value="{{ old('oceanicFL',$booking->getOriginal('oceanicFL')) }}" max="3">

                                @if ($errors->has('oceanicFL'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('oceanicFL') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{--Aircraft--}}
                        <div class="form-group row">
                            <label for="aircraft" class="col-md-4 col-form-label text-md-right">Aircraft code</label>

                            <div class="col-md-6">
                                <div class="form-control-plaintext"><b>{{ $booking->acType ? $booking->acType : '-' }}</b></div>

                            </div>
                        </div>

                        {{--SELCAL--}}
                        <div class="form-group row">
                            <label for="selcal" class="col-md-4 col-form-label text-md-right">SELCAL</label>

                            <div class="col-md-6">
                                <div class="form-control-plaintext"><b>{{ $booking->selcal }}</b></div>

                            </div>
                        </div>

                        {{--Add--}}
                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection