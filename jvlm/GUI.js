// On ready get started with vlm management
// This should be somewhere else...
$(document).ready(
  function(){
    // Load translation strings
    InitLocale();
    
    // Init Menus()
    InitMenusAndButtons();
    
    
    // Init event handlers
    // Login button click event handler
    $(".LoginButton").click( 
      function()
      {
        OnLoginRequest();
      }
    );   
    
    // Show logout menu on drop icon click
    $("#DropLogoutMenu").click(
      function()
      {
        // Show logout menu
        $("#Menu").toggle();
      }
    );
    
    // Handle menu selection
    $("#Menu").menu({select:
                        function (event, ui)
                        {
                          // Get localization key to figure out action
                          switch (ui.item.attr("I18n"))
                          {
                            case "logout":
                              $("#Menu").toggle();
                              Logout();
                              break;
                          }
                          
                        }
                    }
    );
    
    // Set BoatSelector as JQuery UI Selector 
    // Handle boat selector selection change
    //
    $("#BoatSelector").selectmenu();
    $("#BoatSelector").selectmenu({
        select: function( event, ui ) {}
      }
    );
      
    $("#BoatSelector").on( "selectmenuselect", function(event,ui)
      {
        SetCurrentBoat(GetBoatFromIdu(ui.item.value));
      }
    );
    
    // CheckLogin
    CheckLogin();
     
  }  
);

function InitMenusAndButtons()
{
  $( "#Menu" ).menu();
  $( "#Menu" ).hide();
  
  $( "input[type=submit],button" )
      .button()
      .click(function( event ) 
        {
          event.preventDefault();
        }
      );
  
}

function ClearBoatSelector()
{
  $("#BoatSelector").empty();
}

function AddBoatToSelector(boat, isfleet)
{
  var boatclass='';
  if (boat.Engaged && isfleet)
  {
    boatclass = 'RacingBoat';
  }
  else if (boat.Engaged)
  {
    boatclass = 'RacingBSBoat';
  }
  else if (isfleet)
  {
    boatclass = 'Boat';
  }
  else
  {
    boatclass = 'BSBoat';
  }
  
  $("#BoatSelector").append($('<option />',
                                { 
                                  value: boat.IdBoat,
                                  text: boat.BoatName,
                                }
                              )
                            )
  $("option[value="+ boat.IdBoat +"]").toggleClass(false).addClass(boatclass);
}

function   ShowUserBoatSelector()
{
  //$("#BoatSelector").show();
}