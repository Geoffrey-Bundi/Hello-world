Vue.http.headers.common['X-CSRF-TOKEN'] = $("#token").attr("value");

new Vue({

  el: '#manage-item',

  data: {
    items: [],
    pagination: {
        total: 0, 
        per_page: 2,
        from: 1, 
        to: 0,
        current_page: 1
      },
    offset: 4,
    formErrors:{},
    formErrorsUpdate:{},
    newItem : {'pt_id':'','tester_id_range':'','material_id':'','round_id':'','prepared_by':''},
    fillItem : {'pt_id':'','tester_id_range':'','material':'','pt_round':'','prepared_by':'','id':''},
    materials: [],
    rounds: [],
    ranges: []
  },

  computed: {
        isActived: function () {
            return this.pagination.current_page;
        },
        pagesNumber: function () {
            if (!this.pagination.to) {
                return [];
            }
            var from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }
            var to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }
            var pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;
        }
    },

  ready : function(){
  		this.getVueItems(this.pagination.current_page);
        this.loadMaterials();
        this.loadRounds();
        this.loadRanges();
  },

  methods : {

        getVueItems: function(page){
          this.$http.get('/vueitems?page='+page).then((response) => {
            this.$set('items', response.data.data.data);
            this.$set('pagination', response.data.pagination);
          });
        },

        createItem: function(){
		  var input = this.newItem;
		  this.$http.post('/vueitems',input).then((response) => {
		    this.changePage(this.pagination.current_page);
			this.newItem = {'pt_id':'','tester_id_range':'','material':'','pt_round':'','prepared_by':''};
			$("#create-item").modal('hide');
			toastr.success('Item Created Successfully.', 'Success Alert', {timeOut: 5000});
		  }, (response) => {
			this.formErrors = response.data;
	    });
	},

      deleteItem: function(item){
        this.$http.delete('/vueitems/'+item.id).then((response) => {
            this.changePage(this.pagination.current_page);
            toastr.success('Item Deleted Successfully.', 'Success Alert', {timeOut: 5000});
        });
      },

      restoreItem: function(role){
        this.$http.patch('/vueitems/'+role.id+'/restore').then((response) => {
            this.changePage(this.pagination.current_page);
            toastr.success('Item Restored Successfully.', 'Success Alert', {timeOut: 5000});
        });
      },

      editItem: function(item){
          this.fillItem.pt_id = item.pt_id;
          this.fillItem.id = item.id;
          this.fillItem.tester_id_range = item.tester_id_range;
          this.fillItem.material = item.material;
          this.fillItem.pt_round = item.pt_round;
          this.fillItem.prepared_by = item.prepared_by;
          $("#edit-item").modal('show');
      },

      updateItem: function(id){
        var input = this.fillItem;
        this.$http.put('/vueitems/'+id,input).then((response) => {
            this.changePage(this.pagination.current_page);
            this.fillItem = {'pt_id':'','tester_id_range':'','material':'','pt_round':'','prepared_by':'','id':''};
            $("#edit-item").modal('hide');
            toastr.success('Item Updated Successfully.', 'Success Alert', {timeOut: 5000});
          }, (response) => {
              this.formErrorsUpdate = response.data;
          });
      },

      changePage: function (page) {
          this.pagination.current_page = page;
          this.getVueItems(page);
      },

      loadMaterials: function() {
        this.$http.get('/mat').then((response) => {
            this.materials = response.data;

        }, (response) => {
            console.log(response);
        });
      },

      loadRounds: function() {
        this.$http.get('/rnds').then((response) => {
            this.rounds = response.data;

        }, (response) => {
            console.log(response);
        });
      },

      loadRanges: function() {
        this.$http.get('/rng').then((response) => {
            this.ranges = response.data;

        }, (response) => {
            console.log(response);
        });
      }

  }

});