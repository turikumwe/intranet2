<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Insert title here</title>
</head>
<body>
<form method="post" action="<?php echo $this->baseUrl();?>//">
  <table border="0" width="70%" height="60%" align="MIDDLE" valign="MIDDLE">
   <tr>
      <td>
         <label for="date">Date</label>
      </td>
      <td>
         <input id="date" type="text">
      </td>
      <td>
      	<label for="department">Department</label>
         
      </td>
      <td>
         <select id="department">
         </select>
      </td>
   </tr>
   <tr>
      <td>
      	<label for="amount">Amount of Reimbursement</label>
      </td>
      <td>
         <input id="amount" type="text">
      </td>
      <td>
      	<label for="requested">Requested By</label>
      </td>
      <td>
         <select id="requested">
         </select>
      </td>
   </tr>
   <tr>
      <td >
      	<label for="description">Description of Expense</label>
      </td>
      <td colspan="3">
         <input id="description" type="text"  >
      </td>
   </tr>
   <tr>
       <td>
       	<label for="account">Account Number</label>
      </td>
      <td>
         <input id="account" type="text" >
      </td>
      <td>
      	<label for="approved">Approved By</label>
      </td>
      <td>
         <select id="approved">
         </select>
      </td>
   </tr>
   <tr>
      <td>
      	<label for="signature">Signature</label>
         
      </td>
      <td colspan="3">
         <input id="signature" type="checkbox">
      </td>
   </tr>
   <tr>
      <td>
      	<label for="amount_approved">Amount Approved</label>
         
      </td>
      <td>
         <input id="amount_approved" type="text" >
      </td>
      <td>
      	<label for="reveived_by">Received By</label>
         
      </td>
      <td>
         <select id="received_by">
         </select>
      </td>
   </tr>
   <tr>
      <td>
      	<label for="signature2">Signature</label>
      </td>
      <td colspan="3">
         <input id="signature2" type="checkbox">
      </td>
   </tr>
   </table>
</form>
</body>
</html>