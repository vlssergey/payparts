if (typeof module == 'undefined') {
    var module = {};
}

module.PayParts = function(){
    this.isSelected = false;
};

module.PayParts.prototype = {
    initEvents: function(){
        
        var radios = document.forms["co-payment-form"].elements["payment[method]"];
        
        radios.forEach(function(item, i, arr) {
            $(item).observe('change',function(e){
                switch($(this).id){
                    case 'p_method_payparts_payment':
                        $('payment_form_payparts_payment').style.display = "block";
                        $('month-sel').disabled = false;
                        break;
                    default:
                        $('payment_form_payparts_payment').style.display = "none";
                        break;
                }
            });
        });
        
        try{
           this.isSelected = parseInt($('payment_form_payparts_payment').getAttribute('data-is-selected'));
        }catch(err){
            console.log(err);
        }
        
        if (this.isSelected){
            try{
                $('p_method_payparts_payment').click();
            }catch(err){
                console.log(err);
            }
        }
    }
};

document.observe('dom:loaded', function() {
    var payparts = new module.PayParts();
    payparts.initEvents();
});