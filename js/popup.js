$(function(){
    $("a.register").on("click", registerClick);
    $(".overlay").on("click", closePopup)
    
    function registerClick()
    {
        openPopup();
        return false;
    }
    function openPopup()
    {
        $(".overlay").fadeIn(200);
        $(".popup").fadeIn(600);
    }
    function closePopup()
    {
        $(".overlay, .popup").fadeOut(200);
    }
    
    function formSubmit()
    {
        return false;
    }
    
    var $form = $(".popup form");
    if (!Modernizr.input.required || !Modernizr.formvalidation)
    {
        $form.validate({
            errorPlacement: errorPlacement
        });
    }
    function errorPlacement(error, element)
    {
        if (element.attr("type") == "select")
        {
            element.after(error);
        }
        else
        {
            element.attr("placeholder", error.text());
        }
    }
});
