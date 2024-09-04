CREATE TABLE roles(
    roleId INT AUTO_INCREMENT PRIMARY KEY,
    roleName VARCHAR(200) NOT NULL
);

Create Table permissions (
    permissionId INT AUTO_INCREMENT PRIMARY KEY,
    permissionName VARCHAR(200)
);

Create Table rolePermissions(
    id INT AUTO_INCREMENT PRIMARY KEY,
    roleId INT,
    permissionId INT,
   FOREIGN KEY (permissionId) REFERENCES permissions(permissionId),
   FOREIGN KEY (roleId) REFERENCES roles(roleId)
);
CREATE TABLE usrs (
    userId INT AUTO_INCREMENT PRIMARY KEY,
    userName VARCHAR(250) NOT NULL UNIQUE,
    userPassword VARCHAR(500) Not Null,
    userRoleId INT,
FOREIGN KEY (userRoleId) REFERENCES roles(roleId)
);