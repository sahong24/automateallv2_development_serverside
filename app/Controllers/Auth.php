<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use ReflectionException;

class Auth extends BaseController
{
    /**
     * Register a new user
     * @return Response
     * @throws ReflectionException
     */
    public function register()
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|min_length[6]|max_length[50]|valid_email|is_unique[user.email]',
            'password' => 'required|min_length[8]|max_length[255]'
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            return $this->getResponse(
                    $this->validator->getErrors(),
                    ResponseInterface::HTTP_BAD_REQUEST
                );
        } 

        $userModel = new UserModel();
        $userModel->save($input);

        return $this->getJWTForUser(
                $input['email'],
                ResponseInterface::HTTP_CREATED,
                'User Berhasil Registrasi'
            );

    }

    /**
     * Authenticate Existing User
     * @return Response
     */
    public function login()
    {
        $rules = [
            'email' => 'required|min_length[6]|max_length[50]|valid_email',
            'password' => 'required|min_length[8]|max_length[255]|validateUser[email, password]'
        ];

        $errors = [
            'password' => [
                'validateUser' => 'Invalid login credentials provided'
            ]
        ];

        $input = $this->getRequestInput($this->request);


        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this->getResponse(
                    $this->validator->getErrors(),
                    ResponseInterface::HTTP_BAD_REQUEST
                );
        }
       return $this->getJWTForUser(
           $input['email'], 
           ResponseInterface::HTTP_OK,
           'User Berhasil Login'
        );
    }

    private function getJWTForUser(string $emailAddress, int $responseCode, string $message)
    {
        try {
            $model = new UserModel();
            $user = $model->getUserByEmailAddress($emailAddress);
            unset($user['password'], $user['id']);

            helper('jwt');

            return $this->getResponse(
                $message,
                $responseCode,
                [
                    'user' => $user,
                    'access_token' => getSignedJWTForUser($emailAddress),
                ],
            );
        } catch (Exception $exception) {
            return $this->getResponse(
                    $exception->getMessage(),
                    $responseCode,
                );
        }
    }
}