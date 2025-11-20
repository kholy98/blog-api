<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'body'=>$this->body,
            'user'=> new UserResource($this->whenLoaded('user')),
            'created_at'=>$this->created_at,
        ];
    }
}
