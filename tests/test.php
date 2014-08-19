<?php
/* 
 * Password Hashing With PBKDF2 (http://crackstation.net/hashing-security.htm).
 * Copyright (c) 2013, Taylor Hornby
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, 
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation 
 * and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 */
require_once('PasswordHash.php');

if (test()) {
    exit(0);
} else {
    exit(1);
}


function test()
{
    $all_tests_pass = true;

    // Test vector raw output.
    $a = bin2hex(PasswordStorage::pbkdf2("sha1", "password", "salt", 2, 20, true));
    $b = "ea6c014dc72d6f8ccd1ed92ace1d41f0d8de8957";
    if ($a === $b) {
        echo "Test vector 1: pass\n";
    } else { 
        echo "Test vector 1: FAIL\n";
        $all_tests_pass = false;
    }

    // Test vector hex output.
    $a = PasswordStorage::pbkdf2("sha1", "password", "salt", 2, 20, false);
    $b = "ea6c014dc72d6f8ccd1ed92ace1d41f0d8de8957";
    if ($a === $b) {
        echo "Test vector 2: pass\n";
    } else { 
        echo "Test vector 2: FAIL\n";
        $all_tests_pass = false;
    }

    $hash = PasswordStorage::create_verifier("correct_password");

    // Right password returns true.
    $result = PasswordStorage::validate_password("correct_password", $hash);
    if ($result === TRUE)
    {
        echo "Correct password: pass\n";
    }
    else
    {
        echo "Correct password: FAIL\n";
        $all_tests_pass = false;
    }

    // Wrong password returns false.
    $result = PasswordStorage::validate_password("wrong_password", $hash);
    if ($result === FALSE)
    {
        echo "Wrong password: pass\n";
    }
    else
    {
        echo "Wrong password: FAIL\n";
        $all_tests_pass = false;
    }
    
    // Bad verifier raises InvalidVerifierException
    $raised = false;
    try {
        $result = PasswordStorage::validate_password("password", "");
    } catch (InvalidVerifierException $ex) { $raised = true; }
    if ($raised) {
        echo "Bad hash: pass\n";
    } else {
        echo "Bad hash: FAIL\n";
        $all_tests_pass = false;
    }

    // Make sure truncated hashes don't validate.
    $badHashLength = strlen($hash);
    $truncateTest = true;

    do {
        $badHashLength -= 1;
        $badHash = substr($hash, 0, $badHashLength);
        $raised = false;
        try {
            $badResult = PasswordStorage::validate_password("correct_password", $badHash);
        } catch (InvalidVerifierException $ex) { $raised = true; }

        if (!$raised) {
            echo "Truncated hash test: FAIL " . 
                "(At hash length of " . $badHashLength . ") \n";

            $truncateTest = false;
            $all_tests_pass = false;
            break;
        } 
        // The loop goes on until it is two characters away from the last : it
        // finds. This is because the PBKDF2 function requires a hash that's at
        // least 2 characters long. This will be changed once exceptions are
        // implemented.
    } while ($badHash[$badHashLength - 3] != ':');
    
    if($truncateTest) {
        echo "Truncated hash test: pass\n";
    }

    // Make sure changing the algorithm breaks the hash.
    $hash = PasswordStorage::create_verifier("foobar");
    $hash = str_replace("sha1:", "sha256:", $hash);
    if (PasswordStorage::validate_password("foobar", $hash) === FALSE) {
        echo "Algorithm swap: pass\n";
    } else {
        echo "Algorithm swap: FAIL\n";
        $all_tests_pass = false;
    }
    return $all_tests_pass;
}
