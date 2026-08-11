---
project: ansar_recruitment
language: en
---

## [id: product-overview, type: reference] What is Ansar Recruitment?
Ansar Recruitment is a web application (software) that provides an online recruitment portal.
It is maintained by a company called Swapnoloke.
The main purpose of Ansar Recruitment is to let administrators post recruitment circulars (job announcements).
Public users visit the portal to read instructions, view the notice board, and apply for an eligible, active job circular.
The notice board on the portal is updated occasionally, not on a fixed schedule.
A "recruitment circular" is also called a "job circular" or "নিয়োগ বিজ্ঞপ্তি" (niyog biggopti) in Bangla.

## [id: fee-payment-sslcommerz, type: reference] How do I pay the application fee on Ansar Recruitment?
Applicants pay the application fee online using SSLCommerz, a payment gateway.
SSLCommerz is the only supported payment method on Ansar Recruitment.
Before paying, the applicant must first enter their mobile number, labeled "আবেদনকারীর মোবাইল নম্বর" (applicant's mobile number), on the payment page.
After entering the mobile number, the applicant selects "Pay with SSLCommerz".
The applicant then follows SSLCommerz's own on-screen instructions to complete the payment.
The applicant must follow the payment link and steps exactly as shown on the Ansar Recruitment website.
For help with payment problems, applicants can contact 09677112244.

## [id: before-applying-checklist, type: reference] What should I do before applying for a job circular?
Before applying for a specific position on Ansar Recruitment, an applicant should first carefully read the recruitment circular (job announcement) published in the daily newspaper.
The applicant should confirm they meet the eligibility requirements for the position.
The applicant should confirm they have the required work experience, if any is required.
The applicant should confirm they have the necessary documents ready.
The applicant must pay the applicable application fee at the very start of the application process, before completing the rest of the form.
Because payment happens first, applicants are advised to apply only after they fully understand the position's requirements.

## [id: photo-signature-specs, type: reference] What size should my photo and signature be for the application?
During the application process on Ansar Recruitment, an applicant must upload a passport-size photo and a signature image.
Photo size: 250 x 250 pixels.
Signature size: 300 x 100 pixels.
Applicants should confirm their photo and signature files are prepared in these exact sizes, on the computer they are using to apply, before starting the application.

## [id: admit-card-notification, type: reference] How will I know my exam date and get my admit card?
After applying, Ansar Recruitment sends the exam date to the applicant's registered mobile number via SMS.
The same SMS includes instructions for downloading the admit card.
The admit card is also called "প্রবেশপত্র" (probeshpotro) or "entry card" in English.
The admit card includes the applicant's roll number.
The admit card includes the applicant's photo.
The admit card includes other important identifying information about the applicant.

## [id: admit-card-required-for-exams, type: reference] Do I need my admit card to take the exams?
Yes. An applicant must have their admit card (প্রবেশপত্র / entry card) to take part in the recruitment exams.
The applicant cannot take the written exam without the admit card.
The applicant cannot take the health/medical exam without the admit card, in cases where a health exam applies to the position.
The applicant cannot take the oral exam (interview) without the admit card.
The admit card should be printed in color before the exam.

## [id: application-payment-support-contact, type: reference] Who do I contact for problems with applying or paying?
For any problem related to applying for a job circular or making a payment on Ansar Recruitment, applicants can contact this phone number: 09677112244.

## [id: notice-driver-post-reapplication, type: notice] Do I need to reapply for the driver (গাড়ীচালক) position referenced in memo ৪৪.০৩.০০০০.০১৩.২৯.০০১.১৭-১৩?
No, reapplication is not required for this specific case.
This notice is identified by reference memo number: ৪৪.০৩.০০০০.০১৩.২৯.০০১.১৭-১৩.
The memo refers to a recruitment circular that was published in the newspaper on ০৩ জানুয়ারি ২০১৮ (3 January 2018), item/serial number 1 ("ক্রমিক নং-১"), for the driver ("গাড়ীচালক") position.
The 3 January 2018 date identifies which earlier circular this notice applies to — it is not the issue date of this notice itself.
Applicants who had already applied for the driver position under that specific, earlier-published circular do not need to apply again.
Their earlier application remains valid ("বহাল থাকবে").

## OPEN QUESTIONS
- The notes give a reference memo number and the publish date of the earlier circular it points to, but not the issue date of this notice itself. Without that, no `valid_until` can be set. Please provide the notice's own issue/publication date, or confirm how long this notice should remain active.

## DYNAMIC DATA — DO NOT DOCUMENT STATICALLY
- Eligibility rules shown as a plain-text/string message for a specific job circular. Source: DB or API endpoint, per the notes.
- Structured eligibility constraint data for a specific job circular. Source: DB or API endpoint, per the notes.
- FAQ question-and-answer list displayed on the website. Source: DB or API endpoint, per the notes.
- The list of currently active/eligible job circulars shown to public users. This list can change at any time and should be pulled from the DB or API, not embedded as static text.
- Per-circular application deadlines/windows. These are stored in the DB or API per circular and should be fetched live rather than documented as fixed dates.
