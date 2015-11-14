function limit(field, charLimit) {
   if(field.value.length > charLimit) {
      field.value = field.value.substring(0, charLimit);
      alert("You have reached the maximum number of characters in this field.");
   }
}