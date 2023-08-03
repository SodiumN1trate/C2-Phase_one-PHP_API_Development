<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConcertResource;
use App\Http\Resources\SeatingResource;
use App\Models\Booking;
use App\Models\Concert;
use App\Models\LocationSeat;
use App\Models\LocationSeatRow;
use App\Models\Reservation;
use App\Models\Show;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class ConcertController extends Controller
{
    // Get all concerts -> ordered by artist name in ASC
    public function index() {
        return response()->json([
            'concerts' => ConcertResource::collection(Concert::orderBy('artist', 'asc')->get()),
        ]);
    }

    // Get concert by id
    public function show(int $id) {
        $concert = Concert::find($id);
        if (!isset($concert)) {
            return response()->json([
               'error' => 'A concert with this ID does not exist',
            ], 404);
        }

        return response()->json([
            'concert' => new ConcertResource($concert),
        ]);
    }

    public function seating(int $concert_id, int $show_id) {
        $show = Show::find($show_id);
        if(!isset($show) || $show->concert_id != $concert_id) {
            return response()->json([
               'error' => 'A concert or show with this ID does not exist',
            ], 404);
        }
        $location_seat_rows = LocationSeatRow::where('show_id', $show_id)->orderBy('order', 'asc')->get();
        return SeatingResource::collection($location_seat_rows);
    }

    public function reservation(Request $request, int $concert_id, int $show_id) {
        $show = Show::find($show_id);
        $errors = [];
        if(!isset($show) || $show->concert_id != $concert_id) {
            return response()->json([
                'error' => 'A concert or show with this ID does not exist',
            ], 404);
        }

        $validated = $request->validate([
            'reservation_token' => '',
            'reservations' => '',
            'duration' => '',
        ]);

        if(isset($validated['reservation_token'])) {
            $reservation = Reservation::where('token', $validated['reservation_token'])->first();
            if (!isset($reservation)) {
                return response()->json([
                   'error' => 'Invalid reservation token',
                ], 403);
            }
        } else {
            $reservation = Reservation::create([
                'token' => Str::random(8),
            ]);
        }

        foreach ($validated['reservations'] as $row) {
            if (!isset($row['seat'])) {
                $errors += ['reservations' => "Seat field is required"];
            }

            if (!isset($row['row'])) {
                $errors += ['reservations' => "Row field is required"];
            }
            if(count($errors) > 0) {
                return response()->json([
                    'error' => 'Validation failed',
                    'fields' => $errors,
                ], 422);
            }

            $location_seat = LocationSeat::where('location_seat_row_id', $row['row'])->where('number', $row['seat'])->first();
            if (!isset($location_seat)) {
                $errors += ['reservations' => "Seat $location_seat->number in row $location_seat->location_seat_row_id is invalid"];
            }
            if (isset($location_seat->reservation_id) && $location_seat->reservation_id !== $reservation->id) {
                $errors += ['reservations' => "Seat $location_seat->number in row $location_seat->location_seat_row_id is already taken"];
            }
            if(isset($validated['duration']) && ($validated['duration'] < 1 || $validated['duration'] > 300)) {
                $errors += ['duration' => "The duration must be between 1 and 300"];
            }
            if(!isset($validated['duration'])) {
                $validated['duration'] = 300;
            }
            if(count($errors) > 0) {
                return response()->json([
                    'error' => 'Validation failed',
                    'fields' => $errors,
                ], 422);
            }

            $location_seat->reservation_id = $reservation->id;
            $location_seat->save();
        }

        return response()->json([
            'reserved' => true,
            'reservation_token' => $reservation->token,
            'reserved_until' => new \Carbon\Carbon(time() + $validated['duration']),
        ]);

    }

    public function booking(Request $request, int $concert_id, int $show_id) {
        $show = Show::find($show_id);
        if(!isset($show) || $show->concert_id != $concert_id) {
            return response()->json([
                'error' => 'A concert or show with this ID does not exist',
            ], 404);
        }

        $validated = $request->validate([
            'reservation_token' => 'required',
            'name' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'zip' => 'required|string',
            'country' => 'required|string',
        ]);

        $reservation = Reservation::where('token', $validated['reservation_token'])->first();

        if(!isset($reservation)) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        $booking = Booking::create($validated);


        $output = $reservation->seats->map(function ($row) use ($booking, $reservation) {
            $ticket = Ticket::create([
                'code' => Str::random(10),
                'booking_id' => $booking->id,
                'created_at' => date('c'),
            ]);
            return [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'created_at' => $ticket->created_at,
                'row' => [
                    'id' => $row->locationSeatRow->id,
                    'name' => $row->locationSeatRow->name,
                ],
                'seat' => $row->number,
                'show' => [
                    'id' => $row->locationSeatRow->show->id,
                    'start' => $row->locationSeatRow->show->start,
                    'end' => $row->locationSeatRow->show->end,
                    'concert' => [
                        'id' => $row->locationSeatRow->show->concert->id,
                        'artist' => $row->locationSeatRow->show->concert->artist,
                        'location' => [
                            'id' => $row->locationSeatRow->show->concert->location->id,
                            'name' => $row->locationSeatRow->show->concert->location->name,
                        ]
                    ]
                ]
            ];
        });

        return $output;
    }

    public function getTickets(Request $request) {
        $validated = $request->validate([
            'code' => '',
            'name' => '',
        ]);

        $ticket = Ticket::where('code', $validated['code'])->first();
        if(!isset($ticket) || $ticket->booking->name != $validated['name']) {
            return response()->json([
                'error' => 'Unauthorized',
            ]);
        }
//        $output = $reservation->seats->map(function ($row) use ($booking, $reservation) {
//            $ticket = Ticket::create([
//                'code' => Str::random(10),
//                'booking_id' => $booking->id,
//                'created_at' => date('c'),
//            ]);
//            return [
//                'id' => $ticket->id,
//                'code' => $ticket->code,
//                'created_at' => $ticket->created_at,
//                'row' => [
//                    'id' => $row->locationSeatRow->id,
//                    'name' => $row->locationSeatRow->name,
//                ],
//                'seat' => $row->number,
//                'show' => [
//                    'id' => $row->locationSeatRow->show->id,
//                    'start' => $row->locationSeatRow->show->start,
//                    'end' => $row->locationSeatRow->show->end,
//                    'concert' => [
//                        'id' => $row->locationSeatRow->show->concert->id,
//                        'artist' => $row->locationSeatRow->show->concert->artist,
//                        'location' => [
//                            'id' => $row->locationSeatRow->show->concert->location->id,
//                            'name' => $row->locationSeatRow->show->concert->location->name,
//                        ]
//                    ]
//                ]
//            ];
//        });

    }
}
