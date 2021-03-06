/*Function to toggle password fields*/
function toggle(className, obj){
    var $input = $(obj);
    if($input.prop('checked'))
        $(className).hide();
    else
        $(className).show();
}
/*Function to toggle matrix fields*/
function untoggle(className, obj){
    var $input = $(obj);
    if($input.prop('checked'))
        $(className).show();
    else
        $(className).hide();
}
/*Function to toggle options applicable to fields*/
function options(className, obj){
    var $input = $(obj);
    if($input.val() == 4)
        $(className).show();
    else
        $(className).hide();
}
/*Function to toggle facilities for partners and county-lab-coordinators*/
function health(className, obj){
    var $input = $(obj);
    if(($input.val() == 1)||($input.val() == 2))
        $(className).show();
    else
        $(className).hide();
}
/*End toggle function*/
/* Bootstrap 3 datepicker */
$(function () {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
});
/* End datepicker */
//DataTables search functionality
$(document).ready( function () {
	var table = $('.search-table').DataTable({
    	'bStateSave': true,
        //lengthChange: false,
        /*buttons: [ 'copy', 'excel', 'pdf', 'print', 'colvis' ],*/
    	'fnStateSave': function (oSettings, oData) {
        	localStorage.setItem('.search-table', JSON.stringify(oData));
    	},
    	'fnStateLoad': function (oSettings) {
        	return JSON.parse(localStorage.getItem('.search-table'));
    	}
	});
    //table.buttons().container().appendTo( '#example_wrapper .col-md-6:eq(0)' );
});

/*Dynamic loading of select list options for counties-sub-counties*/
$('#county').on('change', function(e){
  var cnty = e.target.value;
     //ajax
     $.get('/api/dropdown/' + cnty, function(data){
         //success data
         $('#sub_county').empty();
         $('#sub_county').append(' ---Select--- ');
         $.each(data, function(index, element){
             $('#sub_county').append("<option value='"+ element.id +"'>" + element.name + "</option>");
         });
     });
 });
 /*Dynamic loading of select list options for sub-counties - facilities*/
 $('#sub_county').on('change', function(e){
   var sbc = e.target.value;
      //ajax
      $.get('/api/dropdown2/' + sbc, function(data){
          //success data
          $('#facility').empty();
          $('#facility').append(' ---Select--- ');
          $.each(data, function(index, element){
              $('#facility').append("<option value='"+ element.id +"'>" + element.name + "</option>");
          });
      });
  });
  /*Dynamic loading of select list options for sub-counties - facilities*/
  $('#facility').on('change', function(e){
    var fclty = e.target.value;
       //ajax
       $.get('/api/dropdown3/' + fclty, function(data){
           //success data
           $('#partner').empty();
           $('#partner').append(' ---Select--- ');
           $.each(data, function(index, element){
               $('#partner').append("<option value='"+ element.id +"'>" + element.name + "</option>");
           });
       });
   });
   /*Function to toggle shipment radios*/
   function toggling(className, obj){
       var $input = $(obj);
       if($input.val() == 0)
       {
          $('#courier').show();
          $('#partners').hide();
          $('#specify').hide();
       }
       else if($input.val() == 1)
       {
          $('#partners').show();
          $('#courier').hide();
          $('#specify').hide();
       }
       else if($input.val() == 3)
       {
          $('#specify').show();
          $('#partners').hide();
          $('#courier').hide();
        }
       else
           $(className).hide();
   }
   /*Function to toggle input fields to specify value if 'other' is selected*/
   function remark(className, obj){
       var $input = $(obj);
       if($input.val() == 4)
           $(className).show();
       else
           $(className).hide();
   }
   /* Function to show county selection */
  function county(id)
  {
      $('.kaunti'+id).toggle(this.checked);
  }
  /* Toggle sub-counties */
  function sub_county(id){
      $('.sub_county'+id).toggle(this.checked);
  }
  /* Toggle facilities */
  function site(id){
      $('.faci'+id).toggle(this.checked);
  }
  /* Function to show partner selection */
 function partner(id)
 {
     $('.part'+id).toggle(this.checked);
 }
 function load(id)
 {
      var cnty = $('#county_'+id).val();
      //ajax
      $.get('/api/dropdown/' + cnty, function(data){
          //success data
          $('#sub_county'+id).empty();
          $('#sub_county'+id).append(' ---Select--- ');
          $.each(data, function(index, element){
              $('#sub_county'+id).append("<option value='"+ element.id +"'>" + element.name + "</option>");
          });
      });
  }
  /*Dynamic loading of select list options for sub-counties - facilities*/
  function drill(id)
  {
     var sbc = $('#sub_county'+id).val();
     //ajax
     $.get('/api/dropdown2/' + sbc, function(data){
         //success data
         $('#facility'+id).empty();
         $('#facility'+id).append(' ---Select--- ');
         $.each(data, function(index, element){
             $('#facility'+id).append("<option value='"+ element.id +"'>" + element.name + "</option>");
         });
     });
 }
