jQuery(document).ready(function($) {

  // Passing options (with default options)
  new Choices('#site_keywords, #site_attribution', {
    removeItemButton: true,
    duplicateItemsAllowed: false,
  });

});

jQuery(document).ready(function($) {


  // Hid standard input
  var inputField = document.getElementById('new_admin_email');
  var currentOwner = inputField.value;
  inputField.setAttribute('hidden', 'true');

  // Append dummy element to use with choices.js
  var choiceElement = document.createElement('select');
  choiceElement.setAttribute('id', 'new_admin_email-choices');
  inputField.insertAdjacentElement('afterend', choiceElement);

  // Construct valid email choices
  var emails = []
  emails.push({
    value: currentOwner,
    label: currentOwner,
    selected: true,
    disabled: false,
  });
  for(var i = 0; i < adminEmails.emails.length; i++) {
    if (adminEmails.emails[i].user_email === currentOwner ) continue;
    emails.push({
      value: adminEmails.emails[i].user_email,
      label: adminEmails.emails[i].user_email,
      selected: false
    });
  }

  // Construct Choices object
  new Choices('#new_admin_email-choices', {
    choices: emails
  });

  // Styling
  $('.choices').css('max-width', '25em');
  
  // Higher order function to update the original input when a choice is selected
  function updateInputValue(inputElement) {
    return function(event) {
      console.log('ding');
      inputElement.value = event.detail.value;
    }
  }

  // Listen for choices selected
  choiceElement.addEventListener('addItem', updateInputValue(inputField));

});
