import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  baseUrl:string="http://localhost/api/"
  constructor(private http:HttpClient) { }

  getAllPermissions(){
   return this.http.get(this.baseUrl + "viewPermissions.php");
  }

  getAllRoles(){
   return this.http.get(this.baseUrl + "viewRoles.php");
  }

  addNewPermission(permissionName:any){
    return this.http.post(this.baseUrl + "insertPermission.php",{"permissionName":permissionName});
  }

  addRole(role:any){
return this.http.post(this.baseUrl + "insertRole.php",{"role":role});
  }
}
