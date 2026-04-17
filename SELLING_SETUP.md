# FitCore Pro - Seller Setup (Local / Demo / Live Payments)

## 1) Prerequisites
- XAMPP with Apache + MySQL
- PHP with `mysqli` enabled
- For real Razorpay payments: `cURL` extension should be enabled in `php.ini`

## 2) Initialize database
1. Open `http://localhost/gym_project/setup.php` in your browser
2. If you already had the database before, you may need:
   - `http://localhost/gym_project/fix_roles.php`
   - `http://localhost/gym_project/fix_online_payments_schema.php`

## 3) Enable online payments (Razorpay)
1. Edit `config.php`
2. Set:
   - `RAZORPAY_KEY_ID`
   - `RAZORPAY_KEY_SECRET`
3. Set `DEMO_MODE`:
   - `true` to test with simulated successful payments
   - `false` for real payments

## 4) Where online payments are used
- Members can renew from: `member_membership.php` (button: “Pay Online & Renew”)
- Server-side payment creation: `start_online_payment.php`
- Payment verification callback: `online_payment_callback.php`

## 5) Online workout plans (courses) - selling module
1. Run/verify: `http://localhost/gym_project/fix_courses_schema.php`
2. Public section on: `http://localhost/gym_project/welcome.php` (cards: “Online Workout Plans”)
3. Checkout flow:
   - `course_checkout.php`
   - `course_payment_callback.php`
4. Buyer access page (members):
   - `course_access.php`
5. Admin view:
   - `admin_course_orders.php`

## 6) Test plan (recommended)
1. Login as admin
2. Add a member (fills email + password in the modal)
3. Login as that member
4. Renew membership (choose a plan and click “Pay Online & Renew”)
5. Confirm:
   - Membership status becomes `Active`
   - `renewal_date` is extended
   - Payment appears in `member_payments.php`

