<?php



namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\StudentProfile $resource
 */
class StudentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'name'                      => $this->user?->name,
            'email'                     => $this->when(
                $request->user()?->id === $this->user_id,
                $this->user?->email
            ),
            'major'                     => $this->whenLoaded('major', fn () => [
                'id'   => $this->major->id,
                'name' => $this->major->name,
            ]),
            'enrollment_number'         => $this->enrollment_number,
            'enrollment_year'           => $this->enrollment_year,
            'expected_graduation_year'  => $this->expected_graduation_year,
            'years_until_graduation'    => $this->years_until_graduation,
            'status'                    => $this->status,
            'created_at'                => $this->created_at,
        ];
    }
}
