<?php /* Hide template from public access ... Next line is subject (unused here) - Following lines are email body
Box Office HTML Template
 <table width="750" border="0">
   <tr>
     <td>
       <table width="100%" border="0">
         <tr>
           <td width="20%"><div align="center">Date/Time</div>
           </td>
           <td width="20%"><div align="center">Ticket Type</div>
           </td>
           <td width="20%"><div align="center">Price</div>
           </td>
           <td width="20%"><div align="center"> Quantity </div>
           </td>
           <td width="20%"><div align="center"> </div>
           </td>
         </tr>
       </table>
     </td>
   </tr>
[startloop]
   <tr>
     <td>
       <input type="hidden" name="os0" value="'.$result->priceType.'"/>
       <input type="hidden" name="hosted_button_id" value="'.$perfPayPalButtonID.'"/>
       <table width="100%" border="0">
         <tr>
           <td width="20%"><div align="center">[xxxxdate]</div>
           </td>
           <td width="20%"><div align="center">[xxxxxxtype]</div>
           </td>
           <td width="20%"><div align="center">[xxxxxprice]</div>
           </td>
           <td width="20%"><div align="center">
               <select name="select">
                 <option value="1" selected="">1</option>
                 <option value="2">2</option>
                 <option value="3">3</option>
                 <option value="4">4</option>
               </select>
             </div>
           </td>
           <td width="20%"><div align="center">
               <input name="[xxxxxxSubmit]" type="submit" value="Add"  alt="Add it!"/>
             </div>
           </td>
         </tr>
       </table>
     </td>
   </tr>
[endloop]   
 </table>
*/ ?>
