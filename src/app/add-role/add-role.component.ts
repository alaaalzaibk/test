import { Component } from '@angular/core';
import { ApiService } from '../api.service';
import { ToastrService } from 'ngx-toastr';
import { FormBuilder,FormGroup, Validators, FormArray, FormControl } from '@angular/forms';
import { Router } from '@angular/router';
// import { MatCheckboxChange } from "@angular/material";


@Component({
  selector: 'app-add-role',
  templateUrl: './add-role.component.html',
  styleUrl: './add-role.component.css'
})
export class AddRoleComponent {

  
  role:FormGroup;
  allPermissions:any;

  constructor(private service:ApiService,private toast:ToastrService,private fb:FormBuilder,private router:Router){
this.role=this.fb.group({
  name:["",Validators.required],
  permissions:this.fb.array([])
}
)
  }

  ngOnInit(){
    this.getAllPermissions();
  }

  onChange(selectedOption: any) {
    const interests = (<FormArray>(
      this.role.get("permissions")
    )) as FormArray;

    if (selectedOption.checked) {
      interests.push(new FormControl(selectedOption.source.value));
    } else {
      const i = interests.controls.findIndex(
        x => x.value === selectedOption.source.value
      );
      interests.removeAt(i);
    }

    console.log(this.role.value);
  }

  addRole(){
    console.log("aaaa",this.role.value.name)
   this.service.addRole(this.role.value).subscribe((data:any)=>{
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

  getAllPermissions(){
    this.service.getAllPermissions().subscribe((data:any)=>{
      this.allPermissions = data.permissions;
    })
  }

}
