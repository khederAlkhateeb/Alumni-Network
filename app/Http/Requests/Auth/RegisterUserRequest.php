<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string' , 'min:3', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:alumni,student'],
            'university_id' => ['required', 'exists:universities,id'],
            'faculty_id'    => ['required', 'exists:faculties,id'],
            'major_id'      => ['required', 'exists:majors,id'],
            'student_number' => ['required_if:role,alumni', 'string', 'max:50'],
            'graduation_year' => ['required_if:role,alumni', 'integer', 'min:1950', 'max:' . date('Y')],
            'enrollment_number' => ['required_if:role,student', 'string', 'max:50'],
            'enrollment_year' => ['required_if:role,student', 'integer', 'min:1950', 'max:' . date('Y')],
            'expected_graduation_year'  => ['required_if:role,student', 'integer', 'min:' . date('Y')],
        ];
    }
    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name must not exceed 255 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'Unable to register with the provided details.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select whether you are a student or an alumnus.',
            'role.in' => 'Role must be either alumni or student.',
            'university_id.required' => 'University is required.',
            'university_id.exists' => 'Selected university is invalid.',
            'faculty_id.required' => 'Faculty is required.',
            'faculty_id.exists' => 'Selected faculty is invalid.',
            'major_id.required' => 'Major is required.',
            'major_id.exists' => 'Selected major is invalid.',
            'student_number.required_if' => 'Student number is required for alumni.',
            'student_number.max' => 'Student number must not exceed 50 characters.',
            'graduation_year.required_if' => 'Graduation year is required for alumni.',
            'graduation_year.integer' => 'Graduation year must be a valid year.',
            'graduation_year.min' => 'Graduation year is invalid.',
            'graduation_year.max' => 'Graduation year cannot be in the future.',
            'enrollment_number.required_if' => 'Enrollment number is required for students.',
            'enrollment_number.max' => 'Enrollment number must not exceed 50 characters.',
            'enrollment_year.required_if' => 'Enrollment year is required for students.',
            'enrollment_year.integer' => 'Enrollment year must be a valid year.',
            'enrollment_year.min' => 'Enrollment year is invalid.',
            'enrollment_year.max' => 'Enrollment year cannot be in the future.',
            'expected_graduation_year.required_if' => 'Expected graduation year is required for students.',
            'expected_graduation_year.integer' => 'Expected graduation year must be a valid year.',
            'expected_graduation_year.min' => 'Expected graduation year must be in the present or future.',
        ];
    }
}
