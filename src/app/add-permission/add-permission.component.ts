import { Component } from '@angular/core';
import { ApiService } from '../api.service';
import { ToastrService } from 'ngx-toastr';
import { FormBuilder,FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';

@Component({
  selector: 'app-add-permission',
  templateUrl: './add-permission.component.html',
  styleUrl: './add-permission.component.css'
})
export class AddPermissionComponent {

  permissionName:FormGroup;

  constructor(private service:ApiService,private toast:ToastrService,private fb:FormBuilder,private router:Router){
this.permissionName=this.fb.group({
  name:["",Validators.required]
}
)
  }

  addPermission(){
    console.log("aaaa",this.permissionName.value.name)
   this.service.addNewPermission(this.permissionName.value.name).subscribe((data:any)=>{
    if(data.success==true){
      this.toast.success(data.message);
      
      //permission added
    }else{
      // permission not added
      this.toast.error(data.message)

    }
    this.router.navigate(["home"])
   }) 
  }

}
