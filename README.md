 *****    *****   *        *****      ***    *****  
*     *  *     *  *        *    *    *   *   *    * 
*        *     *  *        *     *  *     *  *    * 
*  ***   *     *  *        *     *  *******  *****  
*     *  *     *  *        *     *  *     *  *    * 
*     *  *     *  *        *    *   *     *  *    * 
 *****    *****   ******   *****    *     *  *****  

 
## Problem: Mobile login/registration flow

- Capture the user’s mobile number.  
- If the user already exists → login with password.  
- If not → verify via OTP → collect first name / last name and national ID → enter the panel.  

## General Expectation

- Design the solution so that it remains stable and secure at large scale.  
- Briefly document your architectural, security, and data decisions.  
- Implement clean, testable, and maintainable code.  

## Deliverables

- Source code + a short README containing:  
  - An explanation of the architecture and reasons for your choices  
  - How to run the project  
  - Security/scalability points you’ve addressed  
  - A few key tests (preferably automated)  

> **Note:** No real SMS provider integration is required.  
> Simply generate a random 6-digit code, store it securely (hashed with TTL),  
> and simulate sending by logging or showing it in the dev/debug environment.
