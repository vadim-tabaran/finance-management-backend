<?php

namespace App\Controller\Api;

use App\Entity\Expense;
use App\Entity\Tag;
use App\Form\ExpenseType;
use App\Repository\ExpenseRepository;
use App\Service\ApiResponse;
use App\Service\JsonRequest;
use App\Service\ResultFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ExpenseController extends Controller
{
  /**
   * @Route("/expense", name="user_expenses", methods={"GET"})
   */
  public function list(ApiResponse $apiResponse, ExpenseRepository $expenseRepository, ResultFetcher $resultFetcher)
  {
    $expenses = $expenseRepository->findByUser($this->getUser());

    return $apiResponse->appendData($resultFetcher->toArray($expenses))->send();
  }

  /**
   * @Route("/expense/{id}", name="user_expense", methods={"GET"})
   */
  public function details(
    $id,
    ApiResponse $apiResponse,
    ExpenseRepository $expenseRepository,
    ResultFetcher $resultFetcher
  ) {
    $expense = $expenseRepository->find($id);

    if ($expense && $this->getUser()->isEqualTo($expense->getUser())) {
      return $apiResponse->appendData($resultFetcher->toArray($expense))->send();
    }

    return $apiResponse->setMessage('Expense not found')->setCode(ApiResponse::HTTP_NOT_FOUND)->send();
  }

  /**
   * @Route("/expense/create", name="create_expense", methods={"POST"})
   */
  public function create(JsonRequest $request, ApiResponse $apiResponse, EntityManagerInterface $entityManager)
  {
    $expense = new Expense();
    $form = $this->createAndHandleForm($expense, $request->all(), $entityManager);
    if (!$form->isValid()) {
      return $apiResponse->setValidationErrors($form)->send();
    }

    $entityManager->persist($expense);
    $entityManager->flush();

    return $apiResponse->setMessage('Expense created')->send();
  }

  /**
   * @Route("/expense/import", name="import_expense", methods={"POST"})
   */
  public function import(JsonRequest $request, ApiResponse $apiResponse, EntityManagerInterface $entityManager)
  {
    foreach ($request->get('expenses') as $inputExpense) {
      $expense = new Expense();
      $this->createAndHandleForm($expense, $inputExpense, $entityManager);
      $entityManager->persist($expense);
    }

    $entityManager->flush();

    return $apiResponse->setMessage('Expenses are imported')->send();
  }

  /**
   * @Route("/expense/edit/{id}", name="edit_expense", methods={"POST"})
   */
  public function edit($id, JsonRequest $request, ApiResponse $apiResponse, EntityManagerInterface $entityManager)
  {
    $expense = $entityManager->getRepository(Expense::class)->find($id);
    if (!$expense || !$this->getUser()->isEqualTo($expense->getUser())) {
      return $apiResponse->setMessage('Expense not found')->setCode(ApiResponse::HTTP_NOT_FOUND)->send();
    }

    $form = $this->createAndHandleForm($expense, $request->all(), $entityManager);
    if (!$form->isValid()) {
      return $apiResponse->setValidationErrors($form)->send();
    }

    $entityManager->persist($expense);
    $entityManager->flush();

    return $apiResponse->setMessage('Expense updated')->send();
  }

  /**
   * @Route("/expense/delete", name="delete_expense", methods={"POST"})
   */
  public function delete(JsonRequest $request, ApiResponse $apiResponse, EntityManagerInterface $entityManager)
  {
    $expense = $entityManager->getRepository(Expense::class)->find($request->get('id'));

    if (!$expense || !$this->getUser()->isEqualTo($expense->getUser())) {
      return $apiResponse->setMessage('Expense not found')->setCode(ApiResponse::HTTP_NOT_FOUND)->send();
    }

    $entityManager->remove($expense);
    $entityManager->flush();

    return $apiResponse->setMessage('Expense deleted')->send();
  }

  private function getTagIdMap($rawTags, EntityManagerInterface $entityManager)
  {
    $tags = $entityManager->getRepository(Tag::class)->createOrGetExisting($rawTags, $this->getUser());

    $tagIdMap = [];
    foreach ($tags as $tag) {
      $tagIdMap[] = $tag->getId();
    }

    return $tagIdMap;
  }

  private function createAndHandleForm(Expense $expense, $input, EntityManagerInterface $entityManager)
  {
    $tagsIdMap = $this->getTagIdMap($input['tags'], $entityManager);
    $input['tags'] = $tagsIdMap;
    $input['user'] = $this->getUser()->getId();

    $form = $this->createForm(ExpenseType::class, $expense);
    $form->submit($input);

    return $form;
  }
}
