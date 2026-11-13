**IT Helpdesk / Ticketing System for a Small Company**

   
  College of Computer and Information Sciences  
   Polytechnic University of the Philippines	

In partial fulfillment for the course  
COMP 018 \- Database Administration

BSIT 3-5  
> > > > > >         ***Proponents***  
> > > > > >   
Beros, Jose Myrson  
Cardona, Miguel  
Flores, Hannah Lauren  
Hernandez, Harold Benedict  
Panganiban, Lester

# **Project Overview**

#### **Background and Problem**

A small company often has no dedicated helpdesk software. Employees report a broken laptop, a locked account, or a dead network port by chat, email, or by walking over to whoever handles IT. Some requests get forgotten, nobody can see who is working on what, and nobody can say how long a request has been waiting. Equipment is tracked the same loosely way, so the company cannot easily tell which devices keep failing or whether a failing device is still under warranty.

These are data problems. Information on the events exists  (who reported the issue, how urgent it was, who took it, what happened next, which device was involved), but they are scattered or never written down. Without a structured record, simple questions such as “which tickets are late?” or “which technician has the lightest load?”, need manual searching.

#### **Purpose**

This project designs a relational database in MySQL for the IT helpdesk of a small company. The database stores users, departments, tickets, and equipment, and it records each ticket’s due date, status, and history. The goal is to make ticket status, deadlines, technician workload, and repeated equipment failures answerable with queries. This semester’s deliverable is the design (the overview, business rules, ERD, and data dictionary) and a working system built on that design, using HTML, CSS, JavaScript, and PHP on top of the MySQL database.

### **Objectives**

1. Store each user with one role (Employee, Technician, or Admin) and one department.  
2. Record which ticket categories each Technician is qualified to handle.  
3. Record each ticket with a title, description, category, priority, creator, and, once known, an assigned Technician and a related piece of equipment.  
4. Set each ticket’s due date automatically from its priority, and classify unresolved tickets as On Track, At Risk, or Breached.  
5. Keep a history of status changes and comments for every ticket.  
6. Record each piece of equipment with its serial number, status, warranty expiry date, and current holder.  
7. Answer routine reporting questions with SQL: the ticket list, Technician workload, equipment with recurring problems, and tickets that took longer than average.

### **Stakeholders**

Multiple parties receive a direct benefit and contribution to the system in pursuit. These parties are as follows:

* To the employees, who report problems and wait for them to be fixed, the system gives a ticket for every request. They can check its status and due date instead of asking IT in person.  
* To the technicians, who work the tickets, the system gives a list of what is assigned to them with each ticket’s priority and due date. Comments and status changes stay on the ticket, so the next person sees what has already been tried.  
* To the IT administrator, who assigns tickets and manages users and equipment, the system shows each technician’s open workload, which tickets are at risk or breached, and which equipment keeps generating tickets.  
* To the company’s management, who decide how much to spend on IT, the system provides resolution times and a list of devices with repeated problems still under warranty. That information supports repair, claim, and replacement decisions.

### **Core Database Features**

The database has eight tables: departments, users, categories, priorities, technician\_skills, equipment, tickets, and ticket\_updates. Primary keys, foreign keys, NOT NULL, UNIQUE, and CHECK constraints protect the data directly. CHECK constraints limit user roles, ticket status, and equipment status to their allowed values. UNIQUE constraints cover usernames, emails, and equipment serial numbers. The technician\_skills table links Technicians to categories, and its composite primary key prevents the same qualification from being recorded twice.

Each priority stores a resolution target in hours. A BEFORE INSERT trigger on tickets reads that target and sets the ticket’s due date when the ticket is created. A view, v\_ticket\_sla, compares the current time with each ticket’s due date and reports On Track, At Risk, or Breached for unresolved tickets and Met or Breached for resolved ones, along with overdue hours.

The reporting queries cover the following:

* An inner join across tickets, users, categories, and priorities, for the ticket list.  
* An outer join from Technicians to their open tickets, so a Technician with no open tickets still appears in the workload report.  
* An INTERSECT of equipment with three or more tickets in the last 90 days and equipment still under warranty, to find assets worth a warranty claim.  
* A subquery that returns tickets whose resolution time is above the average.

### **Scope and Limitations**

The project covers the database design for ticket tracking, Technician qualifications, due-date monitoring, equipment records, and the reports listed above.

Several things are left out on purpose. Workflow rules are enforced by the application: the order of status changes, assignment only to active Technicians who are qualified for the ticket’s category, locking of resolved tickets, and blocking of retired equipment. Assignment is manual, and no automatic matching of tickets to Technicians is built. A resolved ticket is final and cannot be reopened. If the problem returns, a new ticket is filed. Equipment stores only its current holder, so past custody is not kept. There is no audit log beyond the status changes and comments in ticket\_updates. Due dates use plain clock time, with no business hours or holidays, and only a resolution target is tracked, not a first-response target. Email notifications, file attachments, and chat are out of scope.

### **Business Rules**

The rules below split into two groups. BR1 to BR15 live in the database and hold no matter which application touches the data: keys, NOT NULL, UNIQUE, CHECK, one trigger, one view, and one reporting query. BR16 to BR19 describe workflow and permissions, which the application enforces. That split is deliberate: the database guards the shape and derived values of the data, and the application guards who may do what and in which order.

#### **Users and departments**

| ID | Rule | Enforced by |
| :---- | :---- | :---- |
| BR1 | Each user has exactly one role: Employee, Technician, or Admin. | CHECK on users.role |
| BR2 | Each user belongs to one department. | NOT NULL foreign key to departments |
| BR3 | Usernames and email addresses are unique. | UNIQUE constraints |
| BR4 | Users are deactivated, never deleted. Rows referenced by tickets or equipment cannot be removed. | users.is\_active, ON DELETE RESTRICT on all foreign keys |

#### **Technician qualifications**

| ID | Rule | Enforced by |
| :---- | :---- | :---- |
| BR5 | A Technician may be qualified for any number of categories, and a category may have any number of qualified Technicians. A qualification is recorded once per Technician and category. | technician\_skills (composite primary key, foreign keys to users and categories) |

#### **Tickets**

| ID | Rule | Enforced by |
| :---- | :---- | :---- |
| BR6 | A ticket requires a title, description, category, priority, and creator. Category and priority must come from their lookup tables. Categories are Hardware, Software, Network, Account Access, and Other. Priorities are Low, Medium, High, and Urgent. | NOT NULL, foreign keys to categories, priorities, and users |
| BR7 | Ticket status is Open, In Progress, Resolved. A new ticket starts as Open. | CHECK on tickets.status, DEFAULT ‘Open’ |
| BR8 | A ticket may have one assigned user and one related piece of equipment. Both are optional. | Nullable foreign keys assigned\_to and equipment\_id |
| BR9 | Every status change and comment on a ticket is recorded with the old status, new status, user, and time. Plain comments leave both status fields empty. | ticket\_updates table, rows written by the application |

#### **Due dates**

| ID | Rule | Enforced by |
| :---- | :---- | :---- |
| BR10 | Each priority has one resolution target, in hours. | priorities.resolution\_hours, CHECK greater than 0 |
| BR11 | A ticket’s due date is its creation time plus the resolution target of its priority, set when the ticket is created. | BEFORE INSERT trigger trg\_tickets\_bi |
| BR12 | An unresolved ticket is At Risk when less than 20% of its target time remains and Breached once the due date has passed. Otherwise it is On Track. A resolved ticket is met if resolved on or before the due date and Breached if later. Overdue hours are the hours past the due date, up to now or up to resolution. | View v\_ticket\_sla |

#### **Equipment**

| ID | Rule | Enforced by |
| :---- | :---- | :---- |
| BR13 | Each piece of equipment has a unique serial number and one status: In Stock, Assigned, Under Repair, or Retired. | UNIQUE on serial\_number, CHECK on equipment.status |
| BR14 | A piece of equipment has at most one current holder. Past holders are not kept. | Nullable foreign key assigned\_user\_id |
| BR15 | Equipment has a recurring issue when three or more tickets reference it within 90 days. | Reporting query (INTERSECT with equipment still under warranty) |

#### **Application-enforced rules**

| ID | Rule | Enforced by |
| :---- | :---- | :---- |
| BR16 | Status moves in this order: Open, In Progress, Resolved. Resolved is final, and a resolved ticket cannot be reopened. resolved\_at is set when a ticket becomes Resolved and is not changed afterward. | Application |
| BR17 | Only an Admin assigns tickets, and only to an active Technician who is qualified for the ticket’s category. An Admin adds a missing qualification before assigning. | Application, using technician\_skills |
| BR18 | A resolved ticket cannot be edited. | Application |
| BR19 | Retired equipment cannot be given to a user or linked to a new ticket. | Application |

# **Entity Relationship Diagram**

# **Data Dictionary**

### **departments (lookup)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| department\_id | INT | PK | No | AUTO\_INCREMENT | Unique department ID |
| department\_name | VARCHAR(100) |  | No | UNIQUE | Name of the department |

## 

### **categories (lookup)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| category\_id | INT | PK | No | AUTO\_INCREMENT | Unique category ID |
| category\_name | VARCHAR(50) |  | No | UNIQUE | Hardware, Software, Network, Account Access, Other |

## 

### **priorities (lookup and configuration)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| priority\_id | INT | PK | No | AUTO\_INCREMENT | Unique priority ID |
| priority\_name | VARCHAR(20) |  | No | UNIQUE | Low, Medium, High, Urgent |
| resolution\_hours | INT |  | No | CHECK (resolution\_hours \> 0\) | Hours allowed to resolve a ticket of this priority (BR10) |

### **technician\_skills (new)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| user\_id | INT | PK, FK → users | No |  | The Technician (BR5) |
| category\_id | INT | PK, FK → categories | No |  | A category the Technician is qualified to handle (BR5) |
| added\_at | TIMESTAMP |  | No | DEFAULT CURRENT\_TIMESTAMP | When the qualification was recorded |

## 

### **users (master)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| user\_id | INT | PK | No | AUTO\_INCREMENT | Unique user ID |
| department\_id | INT | FK → departments | No |  | Department the user belongs to |
| username | VARCHAR(50) |  | No | UNIQUE | Login name |
| email | VARCHAR(100) |  | No | UNIQUE | Email address |
| password\_hash | VARCHAR(255) |  | No |  | Hashed password, never plain text |
| full\_name | VARCHAR(100) |  | No |  | Full name |
| role | VARCHAR(20) |  | No | CHECK IN (‘Employee’,’Technician’,’Admin’) | The user’s single role (BR1) |
| is\_active | BOOLEAN |  | No | DEFAULT TRUE | FALSE for deactivated users (BR4) |
| created\_at | TIMESTAMP |  | No | DEFAULT CURRENT\_TIMESTAMP | When the account was created |

##  

### **equipment (master)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| equipment\_id | INT | PK | No | AUTO\_INCREMENT | Unique equipment ID |
| equipment\_type | VARCHAR(50) |  | No |  | Laptop, printer, router, and so on |
| brand | VARCHAR(50) |  | No |  | Manufacturer |
| model | VARCHAR(50) |  | No |  | Model name or number |
| serial\_number | VARCHAR(50) |  | No | UNIQUE | Manufacturer serial number (BR13) |
| status | VARCHAR(20) |  | No | CHECK IN (‘In Stock’,’Assigned’,’Under Repair’,’Retired’), DEFAULT ‘In Stock’ | Current equipment status (BR13) |
| warranty\_expiry | DATE |  | Yes |  | Last day of warranty, empty if unknown or none |
| assigned\_user\_id | INT | FK → users | Yes |  | Current holder, empty if not held (BR14) |

## 

### **tickets (transaction)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| ticket\_id | INT | PK | No | AUTO\_INCREMENT | Unique ticket ID |
| title | VARCHAR(255) |  | No |  | Short summary of the issue |
| description | TEXT |  | No |  | Full description of the issue |
| category\_id | INT | FK → categories | No |  | Ticket category |
| priority\_id | INT | FK → priorities | No |  | Ticket priority |
| status | VARCHAR(20) |  | No | CHECK IN (‘Open’,’In Progress’,’Resolved’), DEFAULT ‘Open’ | Current status (BR7) |
| created\_by | INT | FK → users | No |  | User who reported the issue |
| assigned\_to | INT | FK → users | Yes |  | Technician handling the ticket, empty until assigned |
| equipment\_id | INT | FK → equipment | Yes |  | Equipment the issue concerns, empty if none |
| created\_at | TIMESTAMP |  | No | DEFAULT CURRENT\_TIMESTAMP | When the ticket was created |
| due\_at | TIMESTAMP |  | No | Set by trigger trg\_tickets\_bi | created\_at plus the priority’s resolution\_hours (BR11) |
| resolved\_at | TIMESTAMP |  | Yes |  | When the ticket became Resolved, empty if not resolved |

## 

### **ticket\_updates (transaction)**

| Column | Type | Key | Null | Constraint / default | Description |
| :---- | :---- | :---- | :---- | :---- | :---- |
| update\_id | INT | PK | No | AUTO\_INCREMENT | Unique update ID |
| ticket\_id | INT | FK → tickets | No |  | Ticket the update belongs to |
| user\_id | INT | FK → users | No |  | User who made the update |
| old\_status | VARCHAR(20) |  | Yes | CHECK IN (‘Open’,’In Progress’,’Resolved’)  | Status before the change, empty for plain comments |
| new\_status | VARCHAR(20) |  | Yes | CHECK IN (‘Open’,’In Progress’,’Resolved’) | Status after the change, empty for plain comments |
| comment | TEXT |  | Yes |  | Comment text |
| created\_at | TIMESTAMP |  | No | DEFAULT CURRENT\_TIMESTAMP | When the update was made |