import { Component, ViewChild } from '@angular/core';
import { ApiService } from '../api.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';
import { Router } from '@angular/router';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrl: './home.component.css'
})
export class HomeComponent {
permissions:any=[]
permissionForm:boolean=false;

@ViewChild(MatPaginator) paginator!: MatPaginator;
// dataSource: MatTableDataSource<any>;
dataSource:any

pageSize = 10;
pageSizeOptions = [10, 50, 100];
displayedColumns = ['id', 'permission'];

constructor(private service:ApiService, private router:Router){
this.getAllPermissions();
}

getAllPermissions(){
  this.service.getAllPermissions().subscribe((data:any)=>{
this.permissions=data.permissions;
this.dataSource = new MatTableDataSource(this.permissions);
this.dataSource.paginator = this.paginator;
console.log("permissions",this.permissions)
  })
}

addPermission(){
  this.router.navigate(["add-permission"])
// this.permissionForm=true;
  // this.service.addNewPermission()
}

addRole(){
  this.router.navigate(["add-role"]);
}

}
