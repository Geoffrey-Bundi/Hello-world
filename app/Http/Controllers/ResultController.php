<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Pt;
use App\Result;
use App\Field;
use App\Option;
use App\User;
use App\Notification;

use App\Libraries\AfricasTalkingGateway as Bulk;

use Auth;
use Jenssegers\Date\Date as Carbon;

class ResultController extends Controller
{

    public function manageResult()
    {
        return view('result.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $results = Pt::latest()->withTrashed()->paginate(5);
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $results = Pt::where('pt_id', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
        }
        foreach($results as $result)
        {
            $result->rnd = $result->round->name;
            $result->tester = $result->user->name;
        }
        $response = [
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem()
            ],
            'data' => $results
        ];
        return $results->count() > 0 ? response()->json($response) : $error;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
         //	Save pt first then proceed to save form fields
        $pt = new Pt;
        $pt->round_id = $request->get('round_id');
        $pt->user_id = Auth::user()->id;
        $pt->panel_status = Pt::NOT_CHECKED;
        $pt->save();
        //	Proceed to form-fields
        foreach ($request->all() as $key => $value)
        {
            if((stripos($key, 'token') !==FALSE) || (stripos($key, 'method') !==FALSE))
                continue;
            else if(stripos($key, 'field') !==FALSE)
            {
                $fieldId = $this->strip($key);
                if(is_array($value))
                  $value = implode(', ', $value);
                $result = new Result;
                $result->pt_id = $pt->id;
                $result->field_id = $fieldId;
          		$result->response = $value;
                $result->save();
            }
            else if(stripos($key, 'comment') !==FALSE)
            {
                if($value)
                {
                    $result = Result::where('field_id', $key)->first();
                    $result->comment = $value;
                    $result->save();
                }
            }
        }    
        //  Send SMS
        $round = Round::find($pt->round_id)->name;
        $message = Notification::where('template', Notification::RESULTS_RECEIVED)->first()->message;
        $message = replace_between($message, '[', ']', $round);
        $message = str_replace(' [', ' ', $message);
        $message = str_replace('] ', ' ', $message);

        $created = Carbon::today()->toDateTimeString();
        $updated = Carbon::today()->toDateTimeString();
        //  Time
        $now = Carbon::now('Africa/Nairobi');
        $bulk = DB::table('bulk')->insert(['notification_id' => Notification::RESULTS_RECEIVED, 'round_id' => $pt->round_id, 'text' => $message, 'user_id' => $pt->user_id, 'date_sent' => $now, 'created_at' => $created, 'updated_at' => $updated]);
     
        $recipients = NULL;
        $recipients = User::find($pt->user_id)->value('phone');
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->username;
        $apikey     = $api->api_key;
        if($recipients)
        {
            // Specified sender-id
            $from = $api->code;
            // Create a new instance of Bulk SMS gateway.
            $sms    = new Bulk($username, $apikey);
            // use try-catch to filter any errors.
            try
            {
            // Send messages
            $results = $sms->sendMessage($recipients, $message, $from);
            foreach($results as $result)
            {
                // status is either "Success" or "error message" and save.
                $number = $result->number;
                //  Save the results
                DB::table('broadcast')->insert(['number' => $number, 'bulk_id' => $bulk->id]);
            }
            }
            catch ( AfricasTalkingGatewayException $e )
            {
            echo "Encountered an error while sending: ".$e->getMessage();
            }
        }
        return response()->json('Saved.');
    }

    /**
     * Fetch pt with related components for editing
     *
     * @param ID of the selected pt -  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pt = Pt::find($id);
        $results = $pt->results;
        $response = [
            'pt' => $pt,
            'results' => $results
        ];

        return response()->json($response);
    }
    /*
    verify the result after reviewing
    */
   public function verify($id)
    {
        $user_id = Auth::user()->id;

        $result = Pt::find($id);
        $result->verified_by = $user_id;
        $result->panel_status = Pt::CHECKED;
        $result->save();
        // Send SMS
        $round = Round::find($result->round_id)->name;
        $message = Notification::where('template', Notification::FEEDBACK_RELEASE)->first()->message;
        $message = replace_between($message, '[', ']', $round);
        $message = str_replace(' [', ' ', $message);
        $message = str_replace('] ', ' ', $message);
        
        $created = Carbon::today()->toDateTimeString();
        $updated = Carbon::today()->toDateTimeString();
        //  Time
        $now = Carbon::now('Africa/Nairobi');
        $bulk = DB::table('bulk')->insert(['notification_id' => Notification::FEEDBACK_RELEASE, 'round_id' => $result->round_id, 'text' => $message, 'user_id' => $result->user_id, 'date_sent' => $now, 'created_at' => $created, 'updated_at' => $updated]);
     
        $recipients = NULL;
        $recipients = User::find($result->user_id)->value('phone');
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->username;
        $apikey     = $api->api_key;
        if($recipients)
        {
            // Specified sender-id
            $from = $api->code;
            // Create a new instance of Bulk SMS gateway.
            $sms    = new Bulk($username, $apikey);
            // use try-catch to filter any errors.
            try
            {
            // Send messages
            $results = $sms->sendMessage($recipients, $message, $from);
            foreach($results as $result)
            {
                // status is either "Success" or "error message" and save.
                $number = $result->number;
                //  Save the results
                DB::table('broadcast')->insert(['number' => $number, 'bulk_id' => $bulk->id]);
            }
            }
            catch ( AfricasTalkingGatewayException $e )
            {
            echo "Encountered an error while sending: ".$e->getMessage();
            }
        }
        return response()->json($result);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'round_id' => 'required',
            'date_prepared' => 'required',
            'date_shipped' => 'required',
            'shipping_method' => 'required',
            'shipper_id' => 'required',
            'facility_id' => 'required',
            'panels_shipped' => 'required',
        ]);
        $request->request->add(['user_id' => Auth::user()->id]);

        $edit = Shipment::find($id)->update($request->all());

        return response()->json($edit);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Shipment::find($id)->delete();
        return response()->json(['done']);
    }

    /**
     * enable soft deleted record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id) 
    {
        $shipment = Shipment::withTrashed()->find($id)->restore();
        return response()->json(['done']);
    }

    /**
     * Receive a shipment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function receive(Request $request)
    {
        $this->validate($request, [
            'date_received' => 'required',
            'panels_received' => 'required',
            'condition' => 'required',
            'receiver' => 'required'
        ]);

        $create = Receipt::create($request->all());

        return response()->json($create);
    }
    /**
  	 * Remove the specified begining of text to get Id alone.
  	 *
  	 * @param  int  $id
  	 * @return Response
  	 */
  	public function strip($field)
  	{
    		if(($pos = strpos($field, '_')) !== FALSE)
    		return substr($field, $pos+1);
  	}
}