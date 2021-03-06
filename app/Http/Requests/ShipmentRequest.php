<?php namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Shipment;

class ShipmentRequest extends Request {

  	/**
  	 * Determine if the user is authorized to make this request.
  	 *
  	 * @return bool
  	 */
  	public function authorize()
  	{
  		return true;
  	}

  	/**
  	 * Get the validation rules that apply to the request.
  	 *
  	 * @return array
  	 */
  	public function rules()
  	{
  		$id = $this->ingnoreId();
  		return [
              'round'   => 'required:shipments,round_id,'.$id,
              'date_prepared'   => 'required:shipments,date_prepared,'.$id,
              'date_shipped'   => 'required:shipments,date_shipped,'.$id,
              'shipper'   => 'required:shipments,shipper_id,'.$id,
              'facility'   => 'required:shipments,facility_id,'.$id,
              'panels_shipped'   => 'required:shipments,panels_shipped,'.$id,
          ];
  	}
  	/**
  	* @return \Illuminate\Routing\Route|null|string
  	*/
  	public function ingnoreId()
	{
		$id = $this->route('shipment');
		$round_id = $this->input('round');
        $date_prepared = $this->input('date_prepared');
        $date_shipped = $this->input('date_shipped');
        $shipper_id = $this->input('shipper');
        $facility_id = $this->input('facility');
        $panels_shipped = $this->input('panels_shipped');
    	return Shipment::where(compact('id', 'round_id', 'date_prepared', 'date_shipped', 'shipping_method', 'shipper_id', 'facility_id', 'panels_shipped'))->exists() ? $id : '';
  	}
}
